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
            <button id="clear-history" class="clear-history-button" title="Limpar histórico">
    <i class="fas fa-trash"></i>
</button>
            <button id="ai-assistant-toggle" class="close-button">×</button>
        </div>

        <div class="ai-assistant-body">
            <div id="ai-assistant-messages">
                <!-- Mensagem de boas-vindas -->
                <div class="message assistant-message">
                    <div class="message-content">
                        <img src="https://publicidadeja.com.br/wp-content/uploads/2025/02/icone-ai-zaponto.png" class="assistant-avatar">
                        <div class="message-bubble">
                            Olá! Sou o assistente virtual do Zaponto. Como posso ajudar você hoje?
                        </div>
                    </div>
                </div>
            </div>

            <div class="ai-assistant-input">
                <textarea id="ai-assistant-prompt" placeholder="Digite sua mensagem..." rows="1"></textarea>
                <button id="ai-assistant-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>

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
    max-width: 80%;
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
</style>

<script src="../assets/js/ai-assistant.js"></script>