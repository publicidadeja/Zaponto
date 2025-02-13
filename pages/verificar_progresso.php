<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    exit;
}

// Busca o progresso atual da fila de mensagens
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'ENVIADO' THEN 1 ELSE 0 END) as enviados
    FROM fila_mensagens 
    WHERE usuario_id = ? 
    AND created_at >= NOW() - INTERVAL 1 HOUR
");

$stmt->execute([$_SESSION['usuario_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'total' => (int)$result['total'],
    'enviados' => (int)$result['enviados'],
    'progresso' => $result['total'] > 0 ? 
        round(($result['enviados'] / $result['total']) * 100) : 0
]);