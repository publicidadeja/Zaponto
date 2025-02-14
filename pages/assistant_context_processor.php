<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Acesso não autorizado.']);
    exit;
}

require 'vendor/autoload.php';
require '../includes/db.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class AssistantContextProcessor {
    private $pdo;
    private $usuario_id;
    private $api_key;
    private $api_url;

    public function __construct($pdo, $usuario_id) {
        $this->pdo = $pdo;
        $this->usuario_id = $usuario_id;
        $this->api_key = 'minhaapiaqui'; // Use a mesma key do claude_proxy.php
        $this->api_url = 'https://api.anthropic.com/v1/messages';
    }

    private function getDadosUsuario() {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$this->usuario_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getMetricasUsuario() {
        // Busca quantidade de leads
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total_leads,
                   MAX(created_at) as ultimo_envio,
                   MAX(mensagem) as ultima_mensagem
            FROM leads_enviados 
            WHERE usuario_id = ?
        ");
        $stmt->execute([$this->usuario_id]);
        $leads = $stmt->fetch(PDO::FETCH_ASSOC);

        // Busca frequência de envios (últimos 30 dias)
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as envios_30_dias
            FROM leads_enviados 
            WHERE usuario_id = ? 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$this->usuario_id]);
        $frequencia = $stmt->fetch(PDO::FETCH_ASSOC);

        return array_merge($leads, $frequencia);
    }

    private function construirContexto($dados_usuario, $metricas) {
        return [
            'dados_usuario' => [
                'nome' => $dados_usuario['nome'],
                'email' => $dados_usuario['email'],
                'empresa' => $dados_usuario['empresa'],
                'site' => $dados_usuario['site'],
                'nome_negocio' => $dados_usuario['nome_negocio'],
                'segmento' => $dados_usuario['segmento'],
                'publico_alvo' => $dados_usuario['publico_alvo'],
                'objetivo_principal' => $dados_usuario['objetivo_principal']
            ],
            'metricas' => [
                'total_leads' => $metricas['total_leads'],
                'ultimo_envio' => $metricas['ultimo_envio'],
                'ultima_mensagem' => $metricas['ultima_mensagem'],
                'frequencia_envios' => $metricas['envios_30_dias']
            ]
        ];
    }

    public function processarPrompt($prompt_usuario) {
        try {
            $dados_usuario = $this->getDadosUsuario();
            $metricas = $this->getMetricasUsuario();
            $contexto = $this->construirContexto($dados_usuario, $metricas);

            // Usar caminho absoluto para o arquivo de prompt
            $prompt_base_path = dirname(__FILE__) . '/prompts/assistant_base.txt';
            $prompt_base = file_exists($prompt_base_path) ? file_get_contents($prompt_base_path) : '';

            $prompt_completo = "Contexto do Usuário:\n" . 
                             json_encode($contexto, JSON_PRETTY_PRINT) . 
                             "\n\nPrompt Base do Assistente:\n" . 
                             $prompt_base . 
                             "\n\nPergunta do Usuário:\n" . 
                             $prompt_usuario;

            $client = new Client();
            $response = $client->post($this->api_url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'anthropic-version' => '2023-06-01',
                    'x-api-key' => $this->api_key
                ],
                'json' => [
                    'model' => 'claude-3-haiku-20240307',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt_completo
                        ]
                    ],
                    'max_tokens' => 1000  // Adicionar este parâmetro
                ]
            ]);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            // Tratamento específico da resposta do Claude
            if (isset($data['content']) && is_array($data['content'])) {
                $message = '';
                foreach ($data['content'] as $content) {
                    if ($content['type'] === 'text') {
                        $message .= $content['text'];
                    }
                }
                return ['success' => true, 'content' => $message];
            }

            throw new Exception('Formato de resposta inválido');

        } catch (Exception $e) {
            error_log('Erro no processamento do prompt: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Desculpe, ocorreu um erro ao processar sua mensagem.'];
        }
    }
}

// Uso do processador
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['prompt'])) {
        $processor = new AssistantContextProcessor($pdo, $_SESSION['usuario_id']);
        $resultado = $processor->processarPrompt($input['prompt']);
        
        header('Content-Type: application/json');
        echo json_encode($resultado);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Prompt não fornecido']);
    }
}
?>