<?php

/**
 * Classe GeminiChat - Gerencia a interação com a API Gemini para um chat de assistente de marketing.
 */
class GeminiChat {
    /**
     * @var string Chave da API do Gemini.
     */
    private $apiKey;

    /**
     * @var PDO Conexão com o banco de dados.
     */
    private $pdo;

    /**
     * @var array Contexto do usuário, incluindo perfil, plano, métricas e configurações.
     */
    private $userContext;

    /**
     * @var int ID do usuário.
     */
    private $usuario_id;

    /**
     * @var array Histórico de conversas do usuário.
     */
    private $chatHistory;

    /**
     * @var string Prompt do sistema enviado para a API Gemini.
     */
    private $systemPrompt;

    /**
     * @var int Timestamp da última interação do usuário.
     */
    private $lastInteractionTime;

    /**
     * @var int Número máximo de requisições por minuto.
     */
    private $maxRequestsPerMinute = 30;

    /**
     * @var int Contador de requisições no minuto atual.
     */
    private $requestCount = 0;

    /**
     * Construtor da classe.
     *
     * @param PDO $pdo Conexão com o banco de dados.
     * @param string $apiKey Chave da API do Gemini.
     * @param int $usuario_id ID do usuário.
     */
    public function __construct($pdo, $apiKey, $usuario_id) {
        $this->pdo = $pdo;
        $this->apiKey = $apiKey;
        $this->usuario_id = $usuario_id;
        $this->lastInteractionTime = time();
        $this->initializeChat();
    }

    // Função que gera a sugestão de IA
public function getSuggestion($message) {
    // Prompt específico para sugestões de mensagens
    $suggestionPrompt = [
        "contents" => [
            [
                "parts" => [
                    [
                        "text" => "Você é um especialista em marketing. Melhore a seguinte mensagem para WhatsApp mantendo o mesmo objetivo, mas tornando-a mais persuasiva e profissional. Retorne APENAS a mensagem melhorada, sem explicações ou comentários adicionais:\n\n" . $message
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

    // Chama a API com o prompt específico
    $response = $this->callGeminiAPI($suggestionPrompt);
    
    // Remove possíveis explicações ou comentários extras
    $response = preg_replace('/^(Aqui está|Sugestão:|Mensagem melhorada:|etc\.)/i', '', $response);
    $response = trim($response);
    
    return $response;
}

    /**
     * Inicializa o chat, carregando dados do usuário, histórico e definindo o prompt do sistema.
     */
    private function initializeChat() {
        $this->loadUserData();
        $this->loadHistory();
        $this->setSystemPrompt();
        $this->updateMetrics('chat_iniciado');
    }

    /**
     * Carrega os dados do usuário do banco de dados.
     *
     * @throws PDOException Se houver um erro na consulta SQL.
     */
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

    /**
     * Carrega o histórico de conversas do usuário.
     *  Este método agora inclui o 'sender' (user ou assistant) no histórico retornado.
     *
     * @param int $limit Limite de mensagens a serem carregadas (padrão: 10).
     * @return array Histórico de conversas formatado.
     * @throws PDOException Se houver um erro na consulta SQL.
     */
    public function loadHistory($limit = 10) {
        try {
            // Busca as últimas $limit mensagens em ordem decrescente
            $stmt = $this->pdo->prepare("
                SELECT
                    mensagem,
                    tipo,
                    data_criacao,
                    CASE
                        WHEN tipo = 'user' THEN 'user'
                        ELSE 'assistant'
                    END as sender
                FROM chat_conversations
                WHERE usuario_id = ?
                ORDER BY data_criacao DESC
                LIMIT ?
            ");

            $stmt->execute([$this->usuario_id, $limit]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Inverte a ordem para mostrar as mensagens mais antigas primeiro
            $messages = array_reverse($messages);

            // Formata as mensagens para o frontend, incluindo 'sender'
            $formattedHistory = array_map(function($message) {
                return [
                    'mensagem' => $message['mensagem'],
                    'tipo' => $message['tipo'],  // Mantém o 'tipo' original
                    'data' => date('H:i', strtotime($message['data_criacao'])),
                    'sender' => $message['sender'] // Inclui 'sender' (user/assistant)
                ];
            }, $messages);

            $this->chatHistory = $formattedHistory;
            return $this->chatHistory;

        } catch (PDOException $e) {
            error_log("Erro ao carregar histórico: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Salva o contexto do usuário no banco de dados.
     */
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

    /**
     * Salva uma mensagem no histórico de conversas.
     *
     * @param string $message Mensagem a ser salva.
     * @param string $tipo Tipo da mensagem ('usuario' ou 'assistente').
     */
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

    /**
     * Define o prompt do sistema com base no contexto do usuário.
     */
    private function setSystemPrompt() {
        $this->systemPrompt = "Você é o Assistente de Marketing Especialista do Zaponto, um sistema de CRM focado em comerciantes locais que utilizam WhatsApp para relacionamento com clientes. Você está atendendo {$this->userContext['perfil']['nome']},
        da empresa {$this->userContext['perfil']['empresa']}.

        Contexto do Negócio:
        - Nome do negócio: {$this->userContext['perfil']['nome_negocio']}
        - Segmento: {$this->userContext['perfil']['segmento']}
        - Público-alvo: {$this->userContext['perfil']['publico_alvo']}
        - Objetivo principal: {$this->userContext['perfil']['objetivo_principal']}
        - Site: {$this->userContext['perfil']['site']}
        - Telefone: {$this->userContext['perfil']['telefone']}

        Informações da Assinatura:
        - Plano atual: {$this->userContext['plano']['nome']}
        - Valor do plano: {$this->userContext['plano']['valor']}

        Métricas importantes:
        - Total de leads: {$this->userContext['metricas']['total_leads']}
        - Total de interações: {$this->userContext['metricas']['total_interacoes']}
        - Dias ativos: {$this->userContext['metricas']['dias_ativos']}

        Configurações:
        - Mensagem base: {$this->userContext['configuracoes']['mensagem_base']}
        - Arquivo padrão: {$this->userContext['configuracoes']['arquivo_padrao']}

        CONHECIMENTO ESPECIALIZADO:
        1. Marketing Digital para Pequenos Negócios:
           - Estratégias de WhatsApp Marketing
           - Automação de mensagens
           - Gestão de relacionamento com clientes
           - Marketing local e geolocalizado

        2. Funcionalidades do Sistema:
           - Captura e gestão de leads
           - Automação de mensagens
           - Segmentação de público
           - Análise de métricas
           - Personalização de campanhas

        3. Segmentos de Mercado:
           - Varejo
           - Alimentação
           - Serviços
           - Saúde e Beleza
           - Profissionais Liberais

        DIRETRIZES DE RESPOSTA:
        1. Análise Contextual:
           - Considerar o segmento específico do negócio
           - Avaliar o histórico de envios e resultados
           - Respeitar os limites do plano atual
           - Considerar o nível de maturidade digital

        2. Recomendações:
           - Sugerir estratégias dentro dos limites técnicos
           - Priorizar ações de alto impacto e baixo custo
           - Focar em resultados mensuráveis
           - Adaptar sugestões ao tamanho do negócio

        3. Comunicação:
           - Manter linguagem profissional mas acessível
           - Ser direto e objetivo nas orientações
           - Usar exemplos práticos e relevantes
           - Explicar termos técnicos quando necessário

        4. Conformidade:
           - Respeitar políticas do WhatsApp
           - Seguir boas práticas de marketing
           - Considerar aspectos legais (LGPD)
           - Promover uso ético do marketing

        CAPACIDADES CRIATIVAS:
        1. Geração de Conteúdo:
           - Sugestões de textos para campanhas
           - Templates de mensagens personalizadas
           - Ideias para promoções sazonais
           - Roteiros de campanhas

        2. Otimização:
           - Análise de métricas
           - Sugestões de melhorias
           - Identificação de oportunidades
           - Correção de problemas

        3. Estratégias:
           - Planos de fidelização
           - Campanhas de reativação
           - Ações de engajamento
           - Promoções direcionadas

        Regras e Instruções Específicas:
        - Não compartilhe dados sensíveis do usuário (como email: {$this->userContext['perfil']['email']})
        - Sempre inicie analisando o contexto completo do usuário
        - Considere todas as métricas disponíveis nas recomendações
        - Adapte sugestões aos limites técnicos e do plano
        - Priorize estratégias que maximizem os recursos disponíveis
        - Mantenha foco no objetivo principal do negócio
        - Sugira melhorias baseadas no histórico de resultados
        - Limite as respostas a 200 tokens
        - Mantenha o foco em soluções práticas e alcançáveis

        Versão do contexto: {$this->userContext['versao_contexto']}";
    }

    /**
     * Chama a API do Gemini para gerar uma resposta.
     *
     * @param array $prompt O prompt a ser enviado para a API.
     * @return string A resposta gerada pela API.
     * @throws Exception Se houver um erro na chamada da API ou na resposta.
     */
    private function callGeminiAPI($prompt) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->apiKey;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($prompt));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Erro na chamada da API: ' . curl_error($ch));
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if (isset($result['error'])) {
            throw new Exception('Erro na API do Gemini: ' . $result['error']['message']);
        }

        return $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Desculpe, não consegui gerar uma resposta.';
    }

    /**
     * Constrói o prompt completo a ser enviado para a API Gemini.
     *
     * @param string $message A mensagem do usuário.
     * @return array O prompt formatado para a API.
     */
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

    /**
     * Envia uma mensagem para o assistente e obtém a resposta.
     *
     * @param string $message Mensagem do usuário.
     * @return string Resposta do assistente.
     * @throws Exception Se a mensagem for inválida ou se o limite de requisições for excedido.
     */
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
            throw $e; // Re-lança a exceção para ser tratada no nível superior
        }
    }

    /**
     * Atualiza as métricas de uso do chat.
     *
     * @param string $tipo Tipo da métrica a ser atualizada.
     */
    private function updateMetrics($tipo) {
        $stmt = $this->pdo->prepare("
            INSERT INTO chat_metricas
            (usuario_id, tipo_metrica, valor)
            VALUES (?, ?, 1)
        ");
        $stmt->execute([$this->usuario_id, $tipo]);
    }

    /**
     * Verifica se o limite de requisições por minuto foi atingido.
     *
     * @return bool True se o limite não foi atingido, False caso contrário.
     */
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

    /**
     * Valida a mensagem do usuário.
     *
     * @param string $message Mensagem do usuário.
     * @throws Exception Se a mensagem for inválida.
     */
    private function validateInput($message) {
        if (empty($message)) {
            throw new Exception("A mensagem não pode estar vazia.");
        }
        if (strlen($message) > 1000) {
            throw new Exception("A mensagem excede o limite de caracteres.");
        }
        // Adicione mais validações conforme necessário (ex: caracteres especiais, injeção de código, etc.)
    }

    /**
     * Registra um erro no log de erros.
     *
     * @param Exception $error Exceção capturada.
     */
    private function logError($error) {
        error_log("Erro no chat: " . $error->getMessage());
        $stmt = $this->pdo->prepare("
            INSERT INTO chat_errors
            (usuario_id, erro, data_erro, stack_trace)
            VALUES (?, ?, NOW(), ?)
        ");
        $stmt->execute([
            $this->usuario_id,
            $error->getMessage(),
            $error->getTraceAsString()
        ]);
    }
}

?>