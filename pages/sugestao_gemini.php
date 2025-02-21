<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/GeminiChat.php';

// Verifica autenticação
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

// Verifica se tem acesso à IA
if (!verificarAcessoIA($pdo, $_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Sem acesso à IA']);
    exit;
}

// Recebe a mensagem
$mensagem = $_POST['mensagem'] ?? '';

if (empty($mensagem)) {
    echo json_encode(['success' => false, 'error' => 'Mensagem vazia']);
    exit;
}

try {
    // Gera a sugestão
    $sugestao = gerarSugestaoGemini($pdo, $_SESSION['usuario_id'], $mensagem);
    
    echo json_encode([
        'success' => true,
        'sugestao' => $sugestao
    ]);
    
} catch (Exception $e) {
    error_log("Erro ao gerar sugestão: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao gerar sugestão'
    ]);
}