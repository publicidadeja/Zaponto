<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php'; // Add this line to include authentication functions

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in before accessing session variables
if (!estaLogado()) {
    header('Location: ../pages/login.php');
    exit;
}

// Get user's AI access status
$limites = verificarLimitesUsuario($pdo, $_SESSION['usuario_id']);
$tem_acesso_ia = isset($limites['tem_ia']) ? $limites['tem_ia'] : false;
?>

<div id="ai-assistant-widget" class="ai-assistant-closed">
    <!-- Botão circular flutuante -->
    <div id="ai-assistant-floating-button">
        <img src="https://publicidadeja.com.br/wp-content/uploads/2025/02/icone-ai-zaponto.png" alt="AI Assistant">
    </div>

    <!-- Container principal do chat -->
    <div class="ai-assistant-container">
        <div class="ai-assistant-header">
            <div class="header-content">
                <img src="https://publicidadeja.com.br/wp-content/uploads/2025/02/icone-ai-zaponto.png" alt="AI Assistant" class="header-icon">
                <div class="header-text">
                    <span class="header-title">Assistente IA</span>
                    <span class="header-status">Online</span>
                </div>
            </div>
            <?php if ($tem_acesso_ia): ?>
            <button id="clear-history" class="clear-history-button" title="Limpar histórico">
                <i class="fas fa-trash"></i>
            </button>
            <?php endif; ?>
            <button id="ai-assistant-toggle" class="close-button">×</button>
        </div>

        <div class="ai-assistant-body">
            <div id="ai-assistant-messages">
                <?php if (!$tem_acesso_ia): ?>
                <!-- Mensagem para usuários sem acesso à IA -->
                <div class="message assistant-message">
                    <div class="message-content">
                        <img src="https://publicidadeja.com.br/wp-content/uploads/2025/02/icone-ai-zaponto.png" class="assistant-avatar">
                        <div class="message-bubble">
                            <strong>Acesso Restrito à IA</strong><br><br>
                            Seu plano atual não inclui acesso às funcionalidades de IA. 
                            Para aproveitar estratégias avançadas de marketing no WhatsApp e aumentar suas vendas com a ajuda da nossa IA, considere fazer um upgrade do seu plano.<br><br>
                            <a href="/pages/planos.php" class="upgrade-button">Fazer Upgrade do Plano</a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- Mensagem de boas-vindas para usuários com acesso -->
                <div class="message assistant-message">
                    <div class="message-content">
                        <img src="https://publicidadeja.com.br/wp-content/uploads/2025/02/icone-ai-zaponto.png" class="assistant-avatar">
                        <div class="message-bubble">Olá! Sou o especialista de marketing do Zaponto. Como posso ajudar você hoje?
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($tem_acesso_ia): ?>
            <div class="ai-assistant-input">
                <textarea id="ai-assistant-prompt" placeholder="Digite sua mensagem..." rows="1"></textarea>
                <button id="ai-assistant-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<style>

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
    background: rgba(255,255,255,0.1);
}

#ai-assistant-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    font-family: 'Arial', sans-serif;
}

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

.typing-indicator span:nth-child(2) { animation-delay: 0.15s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.3s; }

@keyframes bounce {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-4px); }
}

#ai-assistant-floating-button {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #0098fc;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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

.ai-assistant-container {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 500px;
    height: 600px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: all 0.3s ease;
    opacity: 0;
    transform: translateY(20px);
    pointer-events: none;
}

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
    background: rgba(255,255,255,0.1);
}

.ai-assistant-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 20px;
    background: #f8f9fa;
    height: calc(100% - 80px); /* Ajusta a altura considerando o header */
    overflow: hidden; /* Importante para manter o layout */
}

#ai-assistant-messages {
    flex: 1;
    overflow-y: auto;
    padding-right: 10px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    height: calc(100% - 60px); /* Ajusta a altura considerando o input */
    max-height: 450px; /* Define uma altura máxima */
    scrollbar-width: thin; /* Para Firefox */
    scrollbar-color: #0098fc #f8f9fa; /* Para Firefox */
}

/* Estilização da scrollbar para Chrome/Safari */
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
    background: white;
    padding: 12px 16px;
    border-radius: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    max-width: 90%;
    line-height: 1.5;
    white-space: pre-line;
}

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
    border-top: 1px solid rgba(0,0,0,0.1);
}

.user-message .message-content {
    flex-direction: row-reverse;
}

.user-message .message-bubble {
    background: #0098fc;
    color: white;
}

.ai-assistant-input {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    background: white;
    padding: 15px;
    border-radius: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
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

@media (max-width: 480px) {
    .ai-assistant-container {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 380px;
    height: 600px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: all 0.3s ease;
    opacity: 0;
    transform: translateY(20px);
    pointer-events: none;
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

.user-message .message-content {
    justify-content: flex-end;
}

.user-message .message-bubble {
    background-color: #0098fc;
    color: white;
    margin-left: auto;
}

.assistant-message .message-bubble {
    background-color: white;
    margin-right: auto;
}
</style>
<script>window.hasAIAccess = <?php echo $tem_acesso_ia ? 'true' : 'false'; ?>;</script>

<script src="../assets/js/ai-assistant.js"></script>