<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/admin-auth.php';
require_once '../../logs/logger.php'; // Adicione esta linha

// Set proper headers
header('Content-Type: application/json; charset=utf-8');

try {
    Logger::log("Iniciando exclusão de notificação", "INFO"); // Log inicial
    
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        Logger::log("ID inválido fornecido: " . print_r($_POST, true), "ERROR");
        throw new Exception('ID inválido');
    }

    $id = (int)$_POST['id'];
    Logger::log("Tentando excluir notificação ID: " . $id, "INFO");
    
    // Marcar notificação como excluída
    $stmt = $pdo->prepare("UPDATE notificacoes SET excluida = 1 WHERE id = ?");
    $success = $stmt->execute([$id]);

    if ($success) {
        Logger::log("Notificação ID: " . $id . " excluída com sucesso", "SUCCESS");
        echo json_encode([
            'success' => true,
            'message' => 'Notificação excluída com sucesso'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        Logger::log("Erro ao excluir notificação ID: " . $id, "ERROR");
        throw new Exception('Erro ao excluir notificação');
    }

} catch (Exception $e) {
    Logger::log("Erro na exclusão: " . $e->getMessage(), "ERROR");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}