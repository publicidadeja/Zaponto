<?php
class GeminiChat {
    private $apiKey;
    private $pdo;
    private $userContext;
    private $usuario_id;
    private $chatHistory;
    private $systemPrompt;
    private $lastInteractionTime;
    private $maxRequestsPerMinute = 30;
    private $requestCount = 0;

    public function __construct($pdo, $apiKey, $usuario_id) {
        $this->pdo = $pdo;
        $this->apiKey = $apiKey;
        $this->usuario_id = $usuario_id;
        $this->lastInteractionTime = time();
        $this->initializeChat();
    }

    private function initializeChat() {
        $this->loadUserData();
        $this->loadHistory();
        $this->setSystemPrompt();
        $this->updateMetrics('chat_iniciado');
    }

    private function loadUserData() {
        // Carrega dados completos do usuário
        $stmt = $this->pdo->prepare("
    SELECT 
        u.*,
        p.nome as plano_nome,
        p.preco as plano_valor,
        (SELECT COUNT(*) FROM leads_enviados WHERE usuario_id = u.id) as total_leads,
        (SELECT COUNT(*) FROM chat_conversations WHERE usuario_id = u.id) as total_interacoes,
        (SELECT COUNT(DISTINCT DATE(created_at)) FROM leads_enviados WHERE usuario_id = u.id) as dias_ativos
    FROM usuarios u
    LEFT JOIN planos p ON u.plano_id = p.id
    WHERE u.id = ?
");
        $stmt->execute([$this->usuario_id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        // Carrega métricas do sistema
        $stmt = $this->pdo->prepare("
            SELECT tipo_metrica, valor, DATE(data_registro) as data
            FROM chat_metricas 
            WHERE usuario_id = ? 
            AND data_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY data_registro DESC
        ");
        $stmt->execute([$this->usuario_id]);
        $metricas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Monta o contexto completo
        $this->userContext = [
            'perfil' => [
                'id' => $userData['id'],
                'nome' => $userData['nome'],
                'email' => $userData['email'],
                'empresa' => $userData['empresa'],
                'site' => $userData['site'],
                'telefone' => $userData['telefone'],
                'nome_negocio' => $userData['nome_negocio'],
                'segmento' => $userData['segmento'],
                'publico_alvo' => $userData['publico_alvo'],
                'objetivo_principal' => $userData['objetivo_principal']
            ],
            'plano' => [
                'nome' => $userData['plano_nome'],
                'valor' => $userData['plano_valor']
            ],
            'metricas' => [
                'total_leads' => $userData['total_leads'],
                'total_interacoes' => $userData['total_interacoes'],
                'dias_ativos' => $userData['dias_ativos'],
                'historico' => $metricas
            ],
            'configuracoes' => [
                'mensagem_base' => $userData['mensagem_base'],
                'arquivo_padrao' => $userData['arquivo_padrao']
            ],
            'versao_contexto' => time()
        ];

        $this->saveContext();
    }

    private function saveContext() {
        $stmt = $this->pdo->prepare("
            INSERT INTO chat_contextos 
            (usuario_id, dados, versao) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $this->usuario_id,
            json_encode($this->userContext),
            $this->userContext['versao_contexto']
        ]);
    }

    private function setSystemPrompt() {
        $this->systemPrompt = "Você é um assistente especializado para {$this->userContext['perfil']['nome']}, 
        da empresa {$this->userContext['perfil']['empresa']}.

        Contexto do Negócio:
        - Segmento: {$this->userContext['perfil']['segmento']}
        - Público-alvo: {$this->userContext['perfil']['publico_alvo']}
        - Objetivo principal: {$this->userContext['perfil']['objetivo_principal']}

        Métricas importantes:
        - Total de leads: {$this->userContext['metricas']['total_leads']}
        - Dias ativos: {$this->userContext['metricas']['dias_ativos']}
        - Plano atual: {$this->userContext['plano']['nome']}

        Configurações:
        - Mensagem base: {$this->userContext['configuracoes']['mensagem_base']}

        Diretrizes:
        1. Forneça respostas personalizadas baseadas no contexto do usuário
        2. Priorize sugestões relacionadas ao segmento e público-alvo
        3. Considere o histórico de interações para contextualizar respostas
        4. Mantenha um tom profissional e alinhado com o objetivo do negócio

        Regras:
        - Não compartilhe dados sensíveis do usuário
        - Mantenha foco no objetivo principal do negócio
        - Sugira melhorias baseadas nas métricas disponíveis";
    }

    public function sendMessage($message) {
        if (!$this->checkRateLimit()) {
            throw new Exception("Limite de requisições excedido. Tente novamente em alguns minutos.");
        }

        try {
            $this->validateInput($message);
            $this->updateMetrics('mensagem_enviada');
            
            // Registra mensagem do usuário
            $this->saveMessage($message, 'usuario');

            // Monta o prompt completo
            $prompt = $this->buildPrompt($message);

            // Chama a API do Gemini
            $response = $this->callGeminiAPI($prompt);

            // Registra resposta do assistente
            $this->saveMessage($response, 'assistente');

            $this->lastInteractionTime = time();
            return $response;

        } catch (Exception $e) {
            $this->logError($e);
            throw $e;
        }
    }

    private function buildPrompt($message) {
        // Recupera histórico recente
        $recentHistory = array_slice($this->chatHistory, -5);
        $conversationContext = "";
        
        foreach ($recentHistory as $msg) {
            $conversationContext .= "{$msg['tipo']}: {$msg['mensagem']}\n";
        }

        return [
            "contents" => [
                [
                    "parts" => [
                        [
                            "text" => $this->systemPrompt . "\n\n" .
                                    "Histórico recente:\n" . $conversationContext . "\n\n" .
                                    "Usuário: " . $message
                        ]
                    ]
                ]
            ],
            "safetySettings" => [
                ["category" => "HARM_CATEGORY_HARASSMENT", "threshold" => "BLOCK_MEDIUM_AND_ABOVE"],
                ["category" => "HARM_CATEGORY_HATE_SPEECH", "threshold" => "BLOCK_MEDIUM_AND_ABOVE"],
                ["category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT", "threshold" => "BLOCK_MEDIUM_AND_ABOVE"],
                ["category" => "HARM_CATEGORY_DANGEROUS_CONTENT", "threshold" => "BLOCK_MEDIUM_AND_ABOVE"]
            ]
        ];
    }

    private function updateMetrics($tipo) {
        $stmt = $this->pdo->prepare("
            INSERT INTO chat_metricas 
            (usuario_id, tipo_metrica, valor) 
            VALUES (?, ?, 1)
        ");
        $stmt->execute([$this->usuario_id, $tipo]);
    }

    public function loadHistory($limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM chat_conversations 
            WHERE usuario_id = ? 
            ORDER BY data_criacao DESC 
            LIMIT ?
        ");
        $stmt->execute([$this->usuario_id, $limit]);
        $this->chatHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->chatHistory;
    }

    private function checkRateLimit() {
        if (time() - $this->lastInteractionTime < 60) {
            $this->requestCount++;
            if ($this->requestCount > $this->maxRequestsPerMinute) {
                return false;
            }
        } else {
            $this->requestCount = 1;
        }
        return true;
    }

    private function validateInput($message) {
        if (empty($message)) {
            throw new Exception("A mensagem não pode estar vazia");
        }
        if (strlen($message) > 1000) {
            throw new Exception("A mensagem excede o limite de caracteres");
        }
        // Adicione mais validações conforme necessário
    }

    private function logError($error) {
        $stmt = $this->pdo->prepare("
            INSERT INTO chat_errors 
            (usuario_id, erro, data_erro) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([
            $this->usuario_id,
            $error->getMessage()
        ]);
    }

    private function saveMessage($message, $tipo) {
        $stmt = $this->pdo->prepare("
            INSERT INTO chat_conversations 
            (usuario_id, mensagem, tipo, contexto, data_criacao) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $this->usuario_id,
            $message,
            $tipo,
            json_encode($this->userContext)
        ]);
    }
}
?>