<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/admin-auth.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('ID inválido');
    }

    $id = (int)$_POST['id'];
    
    // Marcar notificação como excluída
    $stmt = $pdo->prepare("UPDATE notificacoes SET excluida = 1 WHERE id = ?");
    $success = $stmt->execute([$id]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Erro ao excluir notificação');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}