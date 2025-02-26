<?php
// Inicia a sessão.
session_start();

// Inclui as dependências necessárias.
require_once '../includes/db.php';
require_once '../includes/GeminiChat.php';

// Define o cabeçalho da resposta como JSON.  Importante fazer isso antes de qualquer saída.
header('Content-Type: application/json');

// Desativa a exibição de erros no navegador (útil em produção, mas requer um mecanismo de log de erros).
error_reporting(0); // Use error_log() em produção!

try {
    // Verifica se o usuário está logado. Lança uma exceção se não estiver.
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Usuário não autenticado');
    }

    // Instancia a classe GeminiChat.  A chave da API precisa estar definida!
    // Idealmente, $apiKey deve vir de uma variável de ambiente.
    $apiKey = 'AIzaSyBNut_GGHE13BLsaJAE0r0rTo8zFTxV58U'; // ISSO É INSEGURO! USE VARIÁVEIS DE AMBIENTE!
    $chat = new GeminiChat($pdo, $apiKey, $_SESSION['usuario_id']);

    // Carrega o histórico de conversas (últimas 10 mensagens).
    $history = $chat->loadHistory(10);


    // Retorna o histórico como JSON.  O operador ?? garante que $history seja um array vazio se for null.
    echo json_encode([
        'success' => true,
        'data' => $history ?? []
    ]);

} catch (Exception $e) {
    // Em caso de erro, retorna uma resposta JSON com o status de erro e a mensagem.
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>