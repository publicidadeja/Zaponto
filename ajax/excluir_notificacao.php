<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/admin-auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('ID inválido');
    }

    $id = (int)$_POST['id'];
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Marcar notificação como excluída
    $stmt = $pdo->prepare("UPDATE notificacoes SET excluida = 1 WHERE id = ?");
    $success = $stmt->execute([$id]);

    if (!$success) {
        throw new Exception('Erro ao excluir notificação');
    }

    // Remover notificações pendentes
    $stmt = $pdo->prepare("
        DELETE FROM usuario_notificacao 
        WHERE notificacao_id = ? AND status = 'pendente'
    ");
    $stmt->execute([$id]);
    
    // Confirmar transação
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Notificação excluída com sucesso'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}