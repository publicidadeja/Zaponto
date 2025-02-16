<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Requisição recebida: " . file_get_contents('php://input'));

session_start();

header('Content-Type: application/json; charset=utf-8');

if (!file_exists('../vendor/autoload.php') || !file_exists('../includes/db.php')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Required files not found']);
    exit;
}

require '../vendor/autoload.php';
require '../includes/db.php';

// Função para logging
function logError($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "$timestamp - $message\n";
    
    if ($data !== null) {
        $logMessage .= "Data: " . print_r($data, true) . "\n";
    }
    
    $logMessage .= "\n";
    error_log($logMessage);
}

// Função para extrair conteúdo da mensagem
function extractMessageContent($response_data) {
    if (!isset($response_data['content']) || !is_array($response_data['content'])) {
        throw new Exception('Formato de resposta inválido: content ausente ou não é array');
    }

    foreach ($response_data['content'] as $content) {
        if (!isset($content['type']) || !isset($content['text'])) {
            continue;
        }

        if ($content['type'] === 'text') {
            return $content['text'];
        }
    }

    throw new Exception('Nenhum conteúdo de texto encontrado na resposta');
}

class AssistantContextProcessor {
    private $pdo;
    private $usuario_id;
    private $api_key;
    private $api_url;
    private $prompt_base;

    public function __construct($pdo, $usuario_id) {
        $this->pdo = $pdo;
        $this->usuario_id = $usuario_id;
        $this->api_key = 'minha_api_aqui';
        $this->api_url = 'https://api.anthropic.com/v1/messages';
        $this->prompt_base = $this->loadPromptBase();
    }

    private function loadPromptBase() {
        $prompt_path = __DIR__ . '/../prompts/assistant_base.txt';
        if (!file_exists($prompt_path)) {
            throw new Exception('Arquivo de prompt base não encontrado');
        }
        return file_get_contents($prompt_path);
    }

    private function getDadosUsuario() {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.*,
                a.status as status_assinatura,
                a.is_trial,
                a.limite_leads,
                a.limite_mensagens,
                a.tem_ia,
                a.proximo_pagamento, -- Alterado de data_proximo_pagamento para proximo_pagamento
                p.nome as nome_plano,
                d.status as status_dispositivo,
                COUNT(DISTINCT le.id) as total_leads,
                COUNT(DISTINCT CASE WHEN le.data_envio >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN le.id END) as leads_ultimos_30_dias,
                COUNT(DISTINCT CASE WHEN le.status = 'ENVIADO' THEN le.id END) as leads_enviados_sucesso,
                COUNT(DISTINCT CASE WHEN le.status_id = 3 THEN le.id END) as leads_convertidos,
                MAX(le.data_envio) as ultimo_envio,
                (
                    SELECT COUNT(*)
                    FROM mensagens_enviadas me 
                    WHERE me.usuario_id = u.id 
                    AND me.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ) as total_mensagens_mes,
                (
                    SELECT MAX(mensagem)
                    FROM mensagens_enviadas
                    WHERE usuario_id = u.id
                    ORDER BY created_at DESC
                    LIMIT 1
                ) as ultima_mensagem,
                (
                    SELECT COUNT(*)
                    FROM mensagens_enviadas
                    WHERE usuario_id = u.id
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ) / 30 as frequencia_envios,
                (
                    SELECT COUNT(*)
                    FROM ia_interacoes ia 
                    WHERE ia.usuario_id = u.id
                ) as total_interacoes_ia,
                (
                    SELECT COUNT(*)
                    FROM notificacoes n 
                    WHERE n.usuario_id = u.id 
                    AND n.lida = 0
                ) as notificacoes_nao_lidas,
                (
                    SELECT COUNT(*)
                    FROM dispositivos d 
                    WHERE d.usuario_id = u.id 
                    AND d.status = 'CONNECTED'
                ) as dispositivos_conectados
            FROM usuarios u
            LEFT JOIN assinaturas a ON u.id = a.usuario_id AND a.status = 'ativo'
            LEFT JOIN planos p ON a.plano_id = p.id
            LEFT JOIN dispositivos d ON u.id = d.usuario_id
            LEFT JOIN leads_enviados le ON u.id = le.usuario_id
            WHERE u.id = ?
            GROUP BY u.id
        ");
        
        $stmt->execute([$this->usuario_id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Adiciona informações de configurações globais
        $stmt_config = $this->pdo->prepare("SELECT * FROM configuracoes WHERE id = 1");
        $stmt_config->execute();
        $config = $stmt_config->fetch(PDO::FETCH_ASSOC);
        
        return array_merge($dados ?? [], [
            'tempo_entre_envios' => $config['tempo_entre_envios'] ?? 30,
            'max_leads_dia' => $config['max_leads_dia'] ?? 1000,
            'max_mensagens_dia' => $config['max_mensagens_dia'] ?? 1000
        ]);
    }
    

    private function preparePrompt($user_prompt) {
        $dados_usuario = $this->getDadosUsuario();
        
        $contexto = [
            '{{nome_negocio}}' => $dados_usuario['nome_negocio'] ?? 'Não definido',
            '{{segmento}}' => $dados_usuario['segmento'] ?? 'Não definido',
            '{{publico_alvo}}' => $dados_usuario['publico_alvo'] ?? 'Não definido',
            '{{objetivo_principal}}' => $dados_usuario['objetivo_principal'] ?? 'Não definido',
            '{{empresa}}' => $dados_usuario['empresa'] ?? 'Não definido',
            '{{site}}' => $dados_usuario['site'] ?? 'Não definido',
            '{{telefone}}' => $dados_usuario['telefone'] ?? 'Não definido',
            '{{nome_plano}}' => $dados_usuario['nome_plano'] ?? 'Não definido',
            '{{status_assinatura}}' => $dados_usuario['status_assinatura'] ?? 'Inativo',
            '{{is_trial}}' => $dados_usuario['is_trial'] ? 'Sim' : 'Não',
            '{{limite_leads}}' => $dados_usuario['limite_leads'] ?? '0',
            '{{limite_mensagens}}' => $dados_usuario['limite_mensagens'] ?? '0',
            '{{tem_ia}}' => $dados_usuario['tem_ia'] ? 'Sim' : 'Não',
            '{{proximo_pagamento}}' => $dados_usuario['proximo_pagamento'] ?? 'Não definido',
            '{{total_leads}}' => $dados_usuario['total_leads'] ?? '0',
            '{{leads_ultimos_30_dias}}' => $dados_usuario['leads_ultimos_30_dias'] ?? '0',
            '{{leads_convertidos}}' => $dados_usuario['leads_convertidos'] ?? '0',
            '{{leads_enviados_sucesso}}' => $dados_usuario['leads_enviados_sucesso'] ?? '0',
            '{{total_mensagens_mes}}' => $dados_usuario['total_mensagens_mes'] ?? '0',
            '{{ultimo_envio}}' => $dados_usuario['ultimo_envio'] ?? 'Nenhum envio',
            '{{frequencia_envios}}' => number_format($dados_usuario['frequencia_envios'] ?? 0, 1),
            '{{ultima_mensagem}}' => $dados_usuario['ultima_mensagem'] ?? 'Nenhuma mensagem',
            '{{dispositivos_conectados}}' => $dados_usuario['dispositivos_conectados'] ?? '0',
            '{{status_dispositivo}}' => $dados_usuario['status_dispositivo'] ?? 'Desconectado',
            '{{tempo_entre_envios}}' => $dados_usuario['tempo_entre_envios'] ?? '30',
            '{{max_leads_dia}}' => $dados_usuario['max_leads_dia'] ?? '1000',
            '{{max_mensagens_dia}}' => $dados_usuario['max_mensagens_dia'] ?? '1000',
            '{{total_interacoes_ia}}' => $dados_usuario['total_interacoes_ia'] ?? '0',
            '{{notificacoes_nao_lidas}}' => $dados_usuario['notificacoes_nao_lidas'] ?? '0'
        ];
    
        $prompt_completo = $this->prompt_base;
        foreach ($contexto as $key => $value) {
            $prompt_completo = str_replace($key, $value, $prompt_completo);
        }
    
        return $prompt_completo . "\n\nPergunta do usuário: " . $user_prompt;
    }

    public function processMessage($prompt) {
        try {
            logError("Iniciando processamento da mensagem");

            // Preparar o prompt completo com contexto
            $prompt_completo = $this->preparePrompt($prompt);
            
            // Prepara dados para API
            $request_body = json_encode([
                'model' => 'claude-3-haiku-20240307',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt_completo
                    ]
                ],
                'max_tokens' => 1000
            ]);

            logError("Request para API", $request_body);

            // Configuração cURL
            $curl = curl_init();
            if (!$curl) {
                throw new Exception('Falha ao inicializar cURL');
            }

            curl_setopt_array($curl, [
                CURLOPT_URL => $this->api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $request_body,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'anthropic-version: 2023-06-01',
                    'x-api-key: ' . $this->api_key
                ],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);

            // Executa request
            $response = curl_exec($curl);
            $curl_errno = curl_errno($curl);
            $curl_error = curl_error($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            logError("Resposta API (HTTP $http_code)", $response);

            if ($curl_errno) {
                throw new Exception("Erro cURL ($curl_errno): $curl_error");
            }

            curl_close($curl);

            if ($http_code !== 200) {
                $error_data = json_decode($response, true);
                $error_message = isset($error_data['error']['message']) 
                    ? $error_data['error']['message'] 
                    : "HTTP Error: $http_code";
                throw new Exception($error_message);
            }

            // Processa resposta
            $response_data = json_decode($response, true);
            if (!$response_data) {
                throw new Exception('Falha ao decodificar resposta: ' . json_last_error_msg());
            }

            // Extrai conteúdo
            $message_content = extractMessageContent($response_data);
            
            return [
                'success' => true,
                'content' => $message_content
            ];

        } catch (Exception $e) {
            logError("Erro: " . $e->getMessage());
            throw $e;
        }
    }
}

try {
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Acesso não autorizado.');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['prompt'])) {
        throw new Exception('Prompt não fornecido.');
    }

    $processor = new AssistantContextProcessor($pdo, $_SESSION['usuario_id']);
    $response = $processor->processMessage($input['prompt']);
    
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}