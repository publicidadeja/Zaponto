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
 * Formata a resposta da IA para exibição.
 *
 * @param string $resposta A resposta bruta da IA.
 * @return string A resposta formatada em HTML.
 */
function formatarRespostaIA(string $resposta): string
{
    // Remove possíveis tags HTML maliciosas
    $resposta = htmlspecialchars($resposta, ENT_QUOTES, 'UTF-8');
    
    // Converte quebras de linha em tags <br>
    $resposta = nl2br($resposta);
    
    // Formata textos entre asteriscos como negrito
    $resposta = preg_replace('/\*(.*?)\*/', '<strong>$1</strong>', $resposta);
    
    // Monta a estrutura HTML da resposta
    return <<<HTML
    <div class="ia-resposta">
        <div class="ia-header">
            <i class="fas fa-robot"></i>
            <span>Sugestão de Mensagem</span>
        </div>
        <div class="ia-content">
            {$resposta}
        </div>
        <div class="ia-actions">
            <button type="button" class="btn btn-success btn-usar-sugestao" onclick="usarSugestao(this)">
                <i class="fas fa-check me-2"></i>Usar sugestão
            </button>
        </div>
    </div>
    HTML;
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

// Verifica se o usuário tem acesso à IA
if (!verificarAcessoIA($pdo, $_SESSION['usuario_id'])) {
    jsonResponse(false, null, 'Seu plano não inclui acesso à IA');
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

// Criar instância do GeminiChat
$gemini = new GeminiChat($pdo, $config['api_key'], $_SESSION['usuario_id']);

// Obter sugestão usando o novo método específico
$mensagem = trim($_POST['mensagem']);
$sugestaoBruta = $gemini->getSuggestion($mensagem);  // Usa o novo método getSuggestion
$sugestaoFormatada = formatarRespostaIA($sugestaoBruta);

jsonResponse(true, $sugestaoFormatada);

} catch (Exception $e) {
error_log("Erro ao gerar sugestão: " . $e->getMessage());
jsonResponse(false, null, 'Erro ao gerar sugestão: ' . $e->getMessage());
}