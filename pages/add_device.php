<?php
ob_start(); // Previne qualquer saída antes do header
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';
redirecionarSeNaoLogado();

// Garante que não há saída antes de definir o header
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Método não permitido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (!isset($_POST['nome']) || empty(trim($_POST['nome']))) {
        throw new Exception('Nome do dispositivo é obrigatório');
    }

    $nome = trim($_POST['nome']);
    
    if (strlen($nome) < 3 || strlen($nome) > 50) {
        throw new Exception('O nome deve ter entre 3 e 50 caracteres');
    }

    $device_id = 'device_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', uniqid());

    $stmt = $pdo->prepare("
        INSERT INTO dispositivos (usuario_id, nome, device_id, status) 
        VALUES (:usuario_id, :nome, :device_id, 'WAITING_QR')
    ");

    $success = $stmt->execute([
        ':usuario_id' => $_SESSION['usuario_id'],
        ':nome' => $nome,
        ':device_id' => $device_id
    ]);

    if (!$success) {
        throw new Exception('Erro ao adicionar dispositivo');
    }

    ob_clean(); // Limpa qualquer saída anterior
    echo json_encode([
        'success' => true,
        'message' => 'Dispositivo adicionado com sucesso',
        'device_id' => $device_id
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_clean(); // Limpa qualquer saída anterior
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

exit;