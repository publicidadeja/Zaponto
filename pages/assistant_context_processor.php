<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Requisição recebida: " . file_get_contents('php://input'));

session_start();

// Set headers before any output
header('Content-Type: application/json; charset=utf-8');

// caminhos relativos
if (!file_exists('../vendor/autoload.php') || !file_exists('../includes/db.php')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Required files not found']);
    exit;
}

require '../vendor/autoload.php';
require '../includes/db.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

try {
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Acesso não autorizado.');
    }

    // Recebe e decodifica o JSON do request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['prompt'])) {
        throw new Exception('Prompt não fornecido.');
    }

    $prompt = $input['prompt'];

    class AssistantContextProcessor {
        private $pdo;
        private $usuario_id;
        private $api_key;
        private $api_url;

        public function __construct($pdo, $usuario_id) {
            $this->pdo = $pdo;
            $this->usuario_id = $usuario_id;
            $this->api_key = 'sua-chave-api-real-aqui'; // Substitua pela sua chave API real
            $this->api_url = 'https://api.anthropic.com/v1/messages';
        }

        private function getDadosUsuario() {
            $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$this->usuario_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        public function processMessage($prompt) {
            try {
                error_log("Iniciando processamento da mensagem");
                $client = new Client();
                
                $requestData = [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'anthropic-api-key' => $this->api_key,  // Modificado aqui
                        'anthropic-version' => '2023-06-01'
                    ],
                    'json' => [
    'model' => 'claude-3-haiku-20240307',
    'messages' => [
        [
            'role' => 'user',
            'content' => $prompt
        ]
    ],
    'max_tokens' => 1000
]
                ];
                
                error_log("Dados da requisição: " . json_encode($requestData));
                
                $response = $client->post($this->api_url, $requestData);
                
                $result = json_decode($response->getBody(), true);
                error_log("Resposta da API: " . json_encode($result));
                
                return [
                    'success' => true,
                    'content' => $result['content'][0]['text'] ?? 'Resposta não encontrada'
                ];
            } catch (RequestException $e) {
                error_log("Erro na API: " . $e->getMessage());
                throw new Exception('Erro na comunicação com a API: ' . $e->getMessage());
            }
        }
    }

    // Instancia o processador
    $processor = new AssistantContextProcessor($pdo, $_SESSION['usuario_id']);
    
    // Processa a mensagem e obtém a resposta
    $response = $processor->processMessage($prompt);
    
    // Retorna a resposta
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}