<?php

/**
 * ZapLocal - Sugestão Gemini (sugestao_gemini.php)
 *
 * Este script fornece um endpoint para gerar sugestões de mensagens usando a API do Gemini.
 * Ele lida com requisições POST, autenticação, validação de entrada, interação com a classe GeminiChat
 * e retorna a sugestão formatada ou mensagens de erro em formato JSON.
 */

session_start();

require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/GeminiChat.php';

//--------------------------------------------------
// Funções Auxiliares
//--------------------------------------------------

/**
 * Retorna uma resposta JSON padronizada.
 *
 * @param bool $success Indica se a operação foi bem-sucedida.
 * @param string|null $sugestao A sugestão gerada (se houver).
 * @param string|null $error Mensagem de erro (se houver).
 */
function jsonResponse(bool $success, ?string $sugestao = null, ?string $error = null): void
{
    $response = ['success' => $success];
    if ($sugestao !== null) {
        $response['sugestao'] = $sugestao;
    }
    if ($error !== null) {
        $response['error'] = $error;
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

/**
 * Formata a resposta da IA, incluindo negrito e quebras de linha.
 * Essa função agora é definida aqui para ser usada *neste* arquivo também.
 *
 * @param string $resposta A resposta bruta da IA.
 * @return string A resposta formatada em HTML.
 */
function formatarRespostaIA(string $resposta): string
{
    // Remove possíveis tags HTML maliciosas e converte entidades HTML
    $resposta = htmlspecialchars($resposta, ENT_QUOTES, 'UTF-8');

    // Converte quebras de linha em tags <br>
    $resposta = nl2br($resposta);

    // Formata textos entre asteriscos como negrito
    $resposta = preg_replace('/\*(.*?)\*/', '<strong>$1</strong>', $resposta);

    return $resposta;
}

//--------------------------------------------------
// Validação e Autenticação
//--------------------------------------------------

if (!isset($_SESSION['usuario_id'])) {
    jsonResponse(false, null, 'Usuário não autenticado');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['mensagem'])) {
    jsonResponse(false, null, 'Mensagem não fornecida');
}

//--------------------------------------------------
// Processamento da Requisição
//--------------------------------------------------

try {
    // Obter a chave API
    $stmt = $pdo->prepare("SELECT api_key FROM configuracoes WHERE tipo = 'gemini'");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config || empty($config['api_key'])) {
        throw new Exception('Chave API do Gemini não configurada.');
    }

    // Criar instância do GeminiChat
    $gemini = new GeminiChat($pdo, $config['api_key'], $_SESSION['usuario_id']); // Passa a chave API

    // Obter sugestão
    $mensagem = $_POST['mensagem'];
    $sugestaoBruta = $gemini->sendMessage($mensagem);  // Obtém a sugestão bruta
    $sugestaoFormatada = formatarRespostaIA($sugestaoBruta); // Formata a sugestão

    jsonResponse(true, $sugestaoFormatada); // Retorna a sugestão formatada

} catch (Exception $e) {
    error_log("Erro ao gerar sugestão: " . $e->getMessage());
    jsonResponse(false, null, 'Erro ao gerar sugestão: ' . $e->getMessage());
}