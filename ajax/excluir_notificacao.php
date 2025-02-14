<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/admin-auth.php';

// Set proper headers
header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('ID inválido');
    }

    $id = (int)$_POST['id'];
    
    // Marcar notificação como excluída
    $stmt = $pdo->prepare("UPDATE notificacoes SET excluida = 1 WHERE id = ?");
    $success = $stmt->execute([$id]);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Notificação excluída com sucesso'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('Erro ao excluir notificação');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}