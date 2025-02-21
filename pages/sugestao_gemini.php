<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/GeminiChat.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['mensagem'])) {
    echo json_encode(['success' => false, 'error' => 'Mensagem não fornecida']);
    exit;
}

try {
    // Obter a chave API do banco de dados ou arquivo de configuração
    $stmt = $pdo->prepare("SELECT api_key FROM configuracoes WHERE tipo = 'gemini'");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    $apiKey = $config['api_key'];

    // Criar instância do GeminiChat
    $gemini = new GeminiChat($pdo, $apiKey, $_SESSION['usuario_id']);
    
    // Obter sugestão
    $mensagem = $_POST['mensagem'];
    $sugestao = $gemini->sendMessage($mensagem);
    
    echo json_encode(['success' => true, 'sugestao' => $sugestao]);
} catch (Exception $e) {
    error_log("Erro ao gerar sugestão: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao gerar sugestão']);
}