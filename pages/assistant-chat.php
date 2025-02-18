<?php
// widget.php

require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php'; // Certifique-se de que este arquivo existe e tem a função estaLogado()

// Inicia a sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado.  Redireciona para login se não estiver.
if (!estaLogado()) {
    header('Location: ../pages/login.php');
    exit;
}

// Verifica o acesso do usuário à IA.  Usa uma função para maior clareza.
$limites = verificarLimitesUsuario($pdo, $_SESSION['usuario_id']);
$tem_acesso_ia = $limites['tem_ia'] ?? false; // Valor padrão false se não estiver definido

// URL do ícone do assistente (definida aqui para evitar repetição)
$icone_url = 'https://publicidadeja.com.br/wp-content/uploads/2025/02/icone-ai-zaponto.png';

?>

<div id="ai-assistant-widget" class="ai-assistant-closed">
    <!-- Botão Flutuante -->
    <div id="ai-assistant-floating-button">
        <img src="<?php echo htmlspecialchars($icone_url); ?>" alt="AI Assistant">
    </div>

    <!-- Container do Chat -->
    <div class="ai-assistant-container">
        <div class="ai-assistant-header">
            <div class="header-content">
                <img src="<?php echo htmlspecialchars($icone_url); ?>" alt="AI Assistant" class="header-icon">
                <div class="header-text">
                    <span class="header-title">Assistente IA</span>
                    <span class="header-status">Online</span>
                </div>
            </div>
            <?php if ($tem_acesso_ia) : ?>
                <button id="clear-history" class="clear-history-button" title="Limpar histórico" aria-label="Limpar histórico">
                    <i class="fas fa-trash"></i>
                </button>
            <?php endif; ?>
            <button id="ai-assistant-toggle" class="close-button" aria-label="Fechar assistente">×</button>
        </div>

        <div class="ai-assistant-body">
            <div id="ai-assistant-messages" aria-live="polite">
                <?php if (!$tem_acesso_ia) : ?>
                    <!-- Mensagem de Acesso Restrito -->
                    <div class="message assistant-message" role="alert">
                        <div class="message-content">
                            <img src="<?php echo htmlspecialchars($icone_url); ?>" class="assistant-avatar">
                            <div class="message-bubble">
                                <strong>Acesso Restrito à IA</strong><br><br>
                                Seu plano atual não inclui acesso às funcionalidades de IA.
                                Para aproveitar estratégias avançadas de marketing no WhatsApp e aumentar suas vendas com a ajuda da nossa IA, considere fazer um upgrade do seu plano.<br><br>
                                <a href="/pages/planos.php" class="upgrade-button">Fazer Upgrade do Plano</a>
                            </div>
                        </div>
                    </div>
                <?php else : ?>
                    <!-- Mensagem de Boas-Vindas (Usuários com Acesso) -->
                    <div class="message assistant-message">
                        <div class="message-content">
                            <img src="<?php echo htmlspecialchars($icone_url); ?>" class="assistant-avatar">
                            <div class="message-bubble">Olá! Sou o especialista de marketing do Zaponto. Como posso ajudar você hoje?
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($tem_acesso_ia) : ?>
                <div class="ai-assistant-input">
                    <textarea id="ai-assistant-prompt" placeholder="Digite sua mensagem..." rows="1"></textarea>
                    <button id="ai-assistant-send" aria-label="Enviar mensagem">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<style>
    /* Estilos CSS (mantidos e organizados) */

    /* Geral */
    .upgrade-button {
        display: inline-block;
        background-color: #0098fc;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        margin-top: 10px;
        transition: background-color 0.3s ease;
    }

    .upgrade-button:hover {
        background-color: #0076c4;
        text-decoration: none;
        color: white;
    }

    .clear-history-button {
        background: none;
        border: none;
        color: white;
        font-size: 16px;
        cursor: pointer;
        padding: 5px 10px;
        margin-right: 10px;
        border-radius: 5px;
        transition: background 0.3s ease;
    }

    .clear-history-button:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    #ai-assistant-widget {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
        font-family: 'Arial', sans-serif;
    }

    /* Indicador de Digitação */
    .typing-indicator {
        display: flex;
        gap: 4px;
        padding: 4px 8px;
    }

    .typing-indicator span {
        width: 8px;
        height: 8px;
        background: #0098fc;
        border-radius: 50%;
        animation: bounce 1.3s linear infinite;
    }

    .typing-indicator span:nth-child(2) {
        animation-delay: 0.15s;
    }

    .typing-indicator span:nth-child(3) {
        animation-delay: 0.3s;
    }

    @keyframes bounce {
        0%,
        60%,
        100% {
            transform: translateY(0);
        }

        30% {
            transform: translateY(-4px);
        }
    }

    /* Botão Flutuante */
    #ai-assistant-floating-button {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #0098fc;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s ease;
    }

    #ai-assistant-floating-button:hover {
        transform: scale(1.1);
    }

    #ai-assistant-floating-button img {
        width: 35px;
        height: 35px;
    }

    /* Container Principal */
    .ai-assistant-container {
        position: absolute;
        bottom: 80px;
        right: 0;
        width: 500px;
        height: 600px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transition: all 0.3s ease;
        opacity: 0;
        transform: translateY(20px);
        pointer-events: none;
    }

    /* Estados Aberto/Fechado */
    .ai-assistant-closed .ai-assistant-container {
        opacity: 0;
        transform: translateY(20px);
        pointer-events: none;
    }

    #ai-assistant-widget:not(.ai-assistant-closed) .ai-assistant-container {
        opacity: 1;
        transform: translateY(0);
        pointer-events: all;
    }

    /* Cabeçalho */
    .ai-assistant-header {
        padding: 20px;
        background: linear-gradient(135deg, #0098fc, #003d64);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .header-icon {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: white;
        padding: 5px;
    }

    .header-text {
        display: flex;
        flex-direction: column;
    }

    .header-title {
        font-weight: bold;
        font-size: 16px;
    }

    .header-status {
        font-size: 12px;
        opacity: 0.8;
    }

    .close-button {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background 0.3s ease;
    }

    .close-button:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    /* Corpo */
    .ai-assistant-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 20px;
        background: #f8f9fa;
        height: calc(100% - 80px);
        /* Ajuste para o header */
        overflow: hidden;
    }

    #ai-assistant-messages {
        flex: 1;
        overflow-y: auto;
        padding-right: 10px;
        display: flex;
        flex-direction: column;
        gap: 20px;
        height: calc(100% - 60px);
        /* Ajuste para o input */
        max-height: 450px;
        scrollbar-width: thin;
        /* Firefox */
        scrollbar-color: #0098fc #f8f9fa;
        /* Firefox */
    }

    /* Scrollbar (Chrome/Safari) */
    #ai-assistant-messages::-webkit-scrollbar {
        width: 6px;
    }

    #ai-assistant-messages::-webkit-scrollbar-track {
        background: #f8f9fa;
    }

    #ai-assistant-messages::-webkit-scrollbar-thumb {
        background-color: #0098fc;
        border-radius: 3px;
    }

    /* Mensagens */
    .message {
        margin-bottom: 20px;
    }

    .message-content {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .assistant-avatar {
        width: 35px;
        height: 35px;
        border-radius: 50%;
    }

    .message-bubble {
        padding: 12px 16px;
        border-radius: 15px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        max-width: 90%;
        line-height: 1.5;
        white-space: pre-line;
    }

    /* Formatação dentro da bolha */
    .message-bubble strong,
    .message-bubble b {
        font-weight: 600;
    }

    .message-bubble ul,
    .message-bubble ol {
        margin: 10px 0;
        padding-left: 20px;
    }

    .message-bubble p {
        margin: 8px 0;
    }

    .message-bubble h3,
    .message-bubble h4 {
        margin: 12px 0 8px 0;
        font-weight: 600;
    }

    .message-bubble hr {
        margin: 12px 0;
        border: none;
        border-top: 1px solid rgba(0, 0, 0, 0.1);
    }

    /* Mensagens do Usuário */
    .user-message .message-content {
        justify-content: flex-end;
    }

    .user-message .message-bubble {
        background: #0098fc;
        color: white;
        margin-left: auto;
        order: 2;
    }

    .user-message .assistant-avatar {
        order: 1;
    }

    /* Mensagens do Assistente */
    .assistant-message .message-bubble {
        background-color: white;
        margin-right: auto;
    }


    /* Área de Input */
    .ai-assistant-input {
        margin-top: 20px;
        display: flex;
        gap: 10px;
        background: white;
        padding: 15px;
        border-radius: 15px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    #ai-assistant-prompt {
        flex: 1;
        border: none;
        outline: none;
        resize: none;
        padding: 0;
        font-size: 14px;
        max-height: 100px;
    }

    #ai-assistant-send {
        background: #0098fc;
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.2s ease;
    }

    #ai-assistant-send:hover {
        transform: scale(1.1);
    }

    /* Responsividade */
    @media (max-width: 480px) {
        .ai-assistant-container {
            width: 380px;
        }

        #ai-assistant-floating-button {
            width: 50px;
            height: 50px;
        }

        #ai-assistant-floating-button img {
            width: 30px;
            height: 30px;
        }
    }
</style>

<script>
    // Passa o status de acesso à IA para o JavaScript (de forma segura)
    window.hasAIAccess = <?php echo json_encode($tem_acesso_ia); ?>;
</script>
<script src="../assets/js/ai-assistant.js"></script>