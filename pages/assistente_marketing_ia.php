<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Acesso não autorizado.']);
    exit;
}

// Verificar se o usuário tem permissão para usar a IA
function verificarPermissaoIA($usuario_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT p.tem_ia 
        FROM usuarios u 
        JOIN planos p ON u.plano_id = p.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$usuario_id]);
    $resultado = $stmt->fetch();
    
    return $resultado && $resultado['tem_ia'] == 1;
}

// Carregar dados do usuário para contexto
function carregarDadosUsuario($usuario_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            u.nome,
            u.email,
            u.empresa,
            p.nome as plano_nome,
            (SELECT COUNT(*) FROM leads WHERE usuario_id = u.id) as total_leads,
            (SELECT COUNT(*) FROM mensagens_enviadas WHERE usuario_id = u.id) as total_mensagens,
            (SELECT COUNT(*) FROM campanhas WHERE usuario_id = u.id) as total_campanhas
        FROM usuarios u
        JOIN planos p ON u.plano_id = p.id
        WHERE u.id = ?
    ");
    $stmt->execute([$usuario_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Verificar permissão
if (!verificarPermissaoIA($_SESSION['usuario_id'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Seu plano não inclui o assistente de IA.']);
    exit;
}

require 'vendor/autoload.php';
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

$api_key = 'minhasapi'; // Substitua pela sua chave real
$api_url = 'https://api.anthropic.com/v1/messages';

if (isset($_POST['prompt'])) {
    $prompt = $_POST['prompt'];
    $dados_usuario = carregarDadosUsuario($_SESSION['usuario_id']);
    
    // Construir contexto personalizado
    $contexto = "Você é um assistente especializado em marketing digital para o ZapLocal.
    
    Dados do usuário atual:
    - Nome: {$dados_usuario['nome']}
    - Empresa: {$dados_usuario['empresa']}
    - Plano: {$dados_usuario['plano_nome']}
    - Total de Leads: {$dados_usuario['total_leads']}
    - Total de Mensagens Enviadas: {$dados_usuario['total_mensagens']}
    - Total de Campanhas: {$dados_usuario['total_campanhas']}
    
    Com base nesses dados, forneça sugestões personalizadas e relevantes.
    
    Pergunta do usuário: {$prompt}";

    try {
        $client = new Client();
        $response = $client->post($api_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ],
            'json' => [
                'model' => 'claude-3-opus-20240229',
                'max_tokens' => 1000,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $contexto
                    ]
                ]
            ]
        ]);

        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if ($data === null) {
            throw new Exception('Erro ao decodificar resposta da API');
        }

        // Extrair a mensagem da resposta
        $ai_message = '';
        if (isset($data['content']) && is_array($data['content'])) {
            foreach ($data['content'] as $content_item) {
                if (isset($content_item['type']) && $content_item['type'] === 'text') {
                    $ai_message .= $content_item['text'];
                }
            }
        }

        // Registrar a interação para análise futura
        $stmt = $pdo->prepare("
            INSERT INTO ia_interacoes (usuario_id, prompt, resposta, data_criacao)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$_SESSION['usuario_id'], $prompt, $ai_message]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => $ai_message,
            'context' => [
                'total_leads' => $dados_usuario['total_leads'],
                'total_mensagens' => $dados_usuario['total_mensagens'],
                'total_campanhas' => $dados_usuario['total_campanhas']
            ]
        ]);

    } catch (RequestException $e) {
        error_log('Erro na requisição à API da Anthropic: ' . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erro ao processar sua solicitação.']);
    } catch (Exception $e) {
        error_log('Erro geral: ' . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erro interno do servidor.']);
    }
} else {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Prompt não fornecido.']);
}
?>