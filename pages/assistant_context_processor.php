<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

// Set headers before any output
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso não autorizado.']);
    exit;
}

// Check required files
if (!file_exists('vendor/autoload.php') || !file_exists('../includes/db.php')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Required files not found']);
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
        $this->api_key = 'minhaapiaqui';
        $this->api_url = 'https://api.anthropic.com/v1/messages';
    }

    private function getDadosUsuario() {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$this->usuario_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getMetricasUsuario() {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total_leads,
                   MAX(created_at) as ultimo_envio,
                   MAX(mensagem) as ultima_mensagem
            FROM leads_enviados 
            WHERE usuario_id = ?
        ");
        $stmt->execute([$this->usuario_id]);
        $leads = $stmt->fetch(PDO::FETCH_ASSOC);

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
                'nome' => $dados_usuario['nome'] ?? '',
                'email' => $dados_usuario['email'] ?? '',
                'empresa' => $dados_usuario['empresa'] ?? '',
                'site' => $dados_usuario['site'] ?? '',
                'nome_negocio' => $dados_usuario['nome_negocio'] ?? '',
                'segmento' => $dados_usuario['segmento'] ?? '',
                'publico_alvo' => $dados_usuario['publico_alvo'] ?? '',
                'objetivo_principal' => $dados_usuario['objetivo_principal'] ?? ''
            ],
            'metricas' => [
                'total_leads' => $metricas['total_leads'] ?? 0,
                'ultimo_envio' => $metricas['ultimo_envio'] ?? '',
                'ultima_mensagem' => $metricas['ultima_mensagem'] ?? '',
                'frequencia_envios' => $metricas['envios_30_dias'] ?? 0
            ]
        ];
    }

    public function processarPrompt($prompt_usuario) {
        try {
            $dados_usuario = $this->getDadosUsuario();
            $metricas = $this->getMetricasUsuario();
            $contexto = $this->construirContexto($dados_usuario, $metricas);

            $prompt_base_path = dirname(__FILE__) . '/prompts/assistant_base.txt';
            $prompt_base = file_exists($prompt_base_path) ? file_get_contents($prompt_base_path) : '';

            $prompt_completo = "Contexto do Usuário:\n" . 
                             json_encode($contexto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . 
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
                    'max_tokens' => 1000
                ]
            ]);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

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

// Main execution
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input');
        }

        if (!isset($input['prompt'])) {
            throw new Exception('Prompt não fornecido');
        }

        $processor = new AssistantContextProcessor($pdo, $_SESSION['usuario_id']);
        $resultado = $processor->processarPrompt($input['prompt']);

        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);
}