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
}

#ai-assistant-messages {
    flex: 1;
    overflow-y: auto;
    padding-right: 10px;
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
        width: 100vw;
        height: 100vh;
        bottom: 0;
        right: 0;
        border-radius: 0;
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
    document.addEventListener('DOMContentLoaded', function() {
    // Elementos DOM
    const widget = document.getElementById('ai-assistant-widget');
    const floatingButton = document.getElementById('ai-assistant-floating-button');
    const messagesContainer = document.getElementById('ai-assistant-messages');
    const promptInput = document.getElementById('ai-assistant-prompt');
    const sendBtn = document.getElementById('ai-assistant-send');
    const toggleBtn = document.getElementById('ai-assistant-toggle');
    
    // Estado inicial
    let isWidgetOpen = false;
    let isProcessing = false;

    // Função para auto-ajustar altura do textarea
    function autoResizeTextarea(element) {
        element.style.height = 'auto';
        element.style.height = (element.scrollHeight) + 'px';
    }

    // Inicializar textarea auto-resize
    promptInput.addEventListener('input', function() {
        autoResizeTextarea(this);
    });

    // Toggle do widget
    function toggleWidget() {
        isWidgetOpen = !isWidgetOpen;
        widget.classList.toggle('ai-assistant-closed', !isWidgetOpen);
        
        if (isWidgetOpen) {
            floatingButton.style.display = 'none';
            promptInput.focus();
            scrollToBottom();
        } else {
            floatingButton.style.display = 'flex';
        }
    }

    // Event listeners para toggle
    floatingButton.addEventListener('click', toggleWidget);
    toggleBtn.addEventListener('click', toggleWidget);

    // Função para rolar para última mensagem
    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Adicionar mensagem ao chat
    function addMessage(type, content, isLoading = false) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', `${type}-message`);
        
        const messageContent = document.createElement('div');
        messageContent.classList.add('message-content');

        if (type === 'assistant') {
            const avatar = document.createElement('img');
            avatar.src = 'https://publicidadeja.com.br/wp-content/uploads/2025/02/icone-ai-zaponto.png';
            avatar.classList.add('assistant-avatar');
            messageContent.appendChild(avatar);
        }

        const messageBubble = document.createElement('div');
        messageBubble.classList.add('message-bubble');

        if (isLoading) {
            messageBubble.innerHTML = `
                <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            `;
        } else {
            messageBubble.textContent = content;
        }

        messageContent.appendChild(messageBubble);
        messageDiv.appendChild(messageContent);
        messagesContainer.appendChild(messageDiv);
        scrollToBottom();

        return messageDiv;
    }

    // Enviar mensagem
    async function sendMessage() {
    if (isProcessing) return;

    const prompt = promptInput.value.trim();
    if (!prompt) return;

    isProcessing = true;
    promptInput.value = '';
    autoResizeTextarea(promptInput);

    addMessage('user', prompt);
    const loadingMessage = addMessage('assistant', '', true);

    try {
        const response = await fetch('/pages/assistant_context_processor.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ prompt: prompt })
        });

        if (!response.ok) {
            throw new Error('Erro na requisição');
        }

        const data = await response.json();
        loadingMessage.remove();

        if (data.success && data.content) {
            addMessage('assistant', data.content);
        } else {
            throw new Error(data.error || 'Erro desconhecido');
        }
    } catch (error) {
        console.error('Erro:', error);
        loadingMessage.remove();
        addMessage('assistant', 'Desculpe, ocorreu um erro ao processar sua mensagem.');
    } finally {
        isProcessing = false;
        scrollToBottom();
    }
}

    // Event listeners para envio
    sendBtn.addEventListener('click', sendMessage);
    promptInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Detectar clique fora do widget para fechar
    document.addEventListener('click', (e) => {
        if (isWidgetOpen && !widget.contains(e.target) && !floatingButton.contains(e.target)) {
            toggleWidget();
        }
    });

    // Adicionar suporte a drag and drop para arquivos
    widget.addEventListener('dragover', (e) => {
        e.preventDefault();
        widget.classList.add('drag-over');
    });

    widget.addEventListener('dragleave', () => {
        widget.classList.remove('drag-over');
    });

    widget.addEventListener('drop', (e) => {
        e.preventDefault();
        widget.classList.remove('drag-over');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            // Implementar lógica de upload de arquivo aqui
            addMessage('assistant', 'Desculpe, o upload de arquivos ainda não está disponível.');
        }
    });
});
</script>