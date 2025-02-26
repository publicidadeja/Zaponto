<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    // Recebe e decodifica os dados JSON
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($dados['notificacao_id'])) {
        throw new Exception('ID da notificação não fornecido');
    }

    // Prepara e executa a query para atualizar a notificação
    $stmt = $pdo->prepare("
        UPDATE notificacoes 
        SET lida = TRUE, 
            data_leitura = NOW() 
        WHERE id = ? 
        AND usuario_id = ?
    ");

    $success = $stmt->execute([
        $dados['notificacao_id'],
        $_SESSION['usuario_id']
    ]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Erro ao atualizar notificação');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}