<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';
session_start();

// Função para salvar mensagem no histórico
function salvarMensagemAssistente($pdo, $usuario_id, $mensagem, $tipo) {
    try {
        // Verifica se a mensagem já existe para evitar duplicação
        $stmt = $pdo->prepare("
            SELECT id FROM assistant_chat_historico 
            WHERE usuario_id = ? 
            AND mensagem = ? 
            AND tipo_mensagem = ?
            AND DATE(data_criacao) = CURDATE()
            LIMIT 1
        ");
        $stmt->execute([$usuario_id, $mensagem, $tipo]);
        
        if ($stmt->fetch()) {
            return true; // Mensagem já existe, não precisa inserir novamente
        }

        // Insere nova mensagem
        $stmt = $pdo->prepare("
            INSERT INTO assistant_chat_historico 
            (usuario_id, mensagem, tipo_mensagem, data_criacao) 
            VALUES (?, ?, ?, NOW())
        ");
        return $stmt->execute([$usuario_id, $mensagem, $tipo]);
    } catch (PDOException $e) {
        error_log("Erro ao salvar mensagem: " . $e->getMessage());
        return false;
    }
}

// Função para carregar mensagens do histórico
function carregarMensagensAssistente($pdo, $usuario_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT mensagem, tipo_mensagem, data_criacao 
            FROM assistant_chat_historico 
            WHERE usuario_id = ? 
            AND DATE(data_criacao) = CURDATE()
            ORDER BY data_criacao ASC
        ");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao carregar mensagens: " . $e->getMessage());
        return [];
    }
}

// Função para limpar histórico
function limparHistorico($pdo, $usuario_id) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM assistant_chat_historico 
            WHERE usuario_id = ? 
            AND DATE(data_criacao) = CURDATE()
        ");
        return $stmt->execute([$usuario_id]);
    } catch (PDOException $e) {
        error_log("Erro ao limpar histórico: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_SESSION['usuario_id'])) {
            throw new Exception('Usuário não autenticado');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Dados JSON inválidos');
        }

        $response = ['success' => false];

        switch ($data['action']) {
            case 'save':
                if (!isset($data['message']) || !isset($data['type'])) {
                    throw new Exception('Dados incompletos');
                }
                
                $success = salvarMensagemAssistente(
                    $pdo, 
                    $_SESSION['usuario_id'], 
                    $data['message'],
                    $data['type']
                );
                
                $response = [
                    'success' => $success,
                    'message' => $success ? 'Mensagem salva com sucesso' : 'Erro ao salvar mensagem'
                ];
                break;

            case 'load':
                $mensagens = carregarMensagensAssistente($pdo, $_SESSION['usuario_id']);
                $response = [
                    'success' => true,
                    'messages' => array_map(function($msg) {
                        return [
                            'type' => $msg['tipo_mensagem'],
                            'content' => $msg['mensagem'],
                            'timestamp' => $msg['data_criacao']
                        ];
                    }, $mensagens)
                ];
                break;

            case 'clear':
                $success = limparHistorico($pdo, $_SESSION['usuario_id']);
                $response = [
                    'success' => $success,
                    'message' => $success ? 'Histórico limpo com sucesso' : 'Erro ao limpar histórico'
                ];
                break;

            default:
                throw new Exception('Ação inválida');
        }
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'error' => $e->getMessage()
        ];
        http_response_code(400);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}