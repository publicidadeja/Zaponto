<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/admin-auth.php';
require_once '../includes/notifications.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('ID inválido');
    }

    $id = (int)$_POST['id'];
    
    if (excluirNotificacao($pdo, $id)) {
        echo json_encode([
            'success' => true,
            'message' => 'Notificação excluída com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao excluir notificação');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}