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
            SELECT 
                mensagem, 
                tipo_mensagem as type,
                DATE_FORMAT(data_criacao, '%Y-%m-%d %H:%i:%s') as timestamp
            FROM assistant_chat_historico 
            WHERE usuario_id = ? 
            AND DATE(data_criacao) = CURDATE()
            ORDER BY data_criacao ASC
        ");
        $stmt->execute([$usuario_id]);
        return [
            'success' => true,
            'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    } catch (PDOException $e) {
        error_log("Erro ao carregar mensagens: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Erro ao carregar mensagens'
        ];
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
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'load':
                if (!isset($_SESSION['usuario_id'])) {
                    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
                    exit;
                }
                $messages = carregarMensagensAssistente($pdo, $_SESSION['usuario_id']);
                echo json_encode($messages);
                exit;
                break;
            
            case 'save':
                if (!isset($_SESSION['usuario_id'])) {
                    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
                    exit;
                }

                $mensagem = $data['message'] ?? '';
                $tipo = $data['type'] ?? 'user';

                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO assistant_chat_historico 
                        (usuario_id, mensagem, tipo_mensagem, data_criacao) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    
                    $stmt->execute([
                        $_SESSION['usuario_id'],
                        $mensagem,
                        $tipo
                    ]);

                    echo json_encode([
                        'success' => true,
                        'message' => 'Mensagem salva com sucesso'
                    ]);
                } catch (PDOException $e) {
                    error_log("Erro ao salvar mensagem: " . $e->getMessage());
                    echo json_encode([
                        'success' => false,
                        'error' => 'Erro ao salvar mensagem'
                    ]);
                }
                exit;
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Ação inválida']);
                exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Ação não especificada']);
        exit;
    }
}

// Função para limpar mensagens antigas (mais de 24 horas)
function limparMensagensAntigas($pdo) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM assistant_chat_historico 
            WHERE data_criacao < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $result = $stmt->execute();
        
        if ($result) {
            // Log da limpeza para monitoramento
            error_log("Limpeza automática do histórico: " . $stmt->rowCount() . " mensagens removidas");
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Erro ao limpar mensagens antigas: " . $e->getMessage());
        return false;
    }
}