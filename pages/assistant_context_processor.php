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
        $this->api_key = 'sua_api_key_aqui';
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

            // Construir o prompt completo
            $prompt_completo = "Contexto do Usuário:\n" . 
                             json_encode($contexto, JSON_PRETTY_PRINT) . 
                             "\n\nPrompt Base do Assistente:\n" . 
                             file_get_contents('prompts/assistant_base.txt') . 
                             "\n\nPergunta do Usuário:\n" . 
                             $prompt_usuario;

            // Fazer a chamada à API
            $client = new Client();
            $response = $client->post($this->api_url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $this->api_key,
                    'Anthropic-Version' => '2023-06-01'
                ],
                'json' => [
                    'model' => 'claude-3.5-haiku-20240620',
                    'max_tokens' => 1000,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt_completo
                        ]
                    ]
                ]
            ]);

            $body = (string) $response->getBody();
            return json_decode($body, true);

        } catch (Exception $e) {
            error_log('Erro no processamento do prompt: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// Uso do processador
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['prompt'])) {
    $processor = new AssistantContextProcessor($pdo, $_SESSION['usuario_id']);
    $resultado = $processor->processarPrompt($_POST['prompt']);
    
    header('Content-Type: application/json');
    echo json_encode($resultado);
}
?>