document.addEventListener('DOMContentLoaded', function() {
    // Elementos DOM
    const widget = document.getElementById('ai-assistant-widget');
    const floatingButton = document.getElementById('ai-assistant-floating-button');
    const messagesContainer = document.getElementById('ai-assistant-messages');
    const promptInput = document.getElementById('ai-assistant-prompt');
    const sendBtn = document.getElementById('ai-assistant-send');
    const toggleBtn = document.getElementById('ai-assistant-toggle');

    // Verifica se o usuário tem acesso à IA (definido no PHP)
    const hasAIAccess = window.hasAIAccess || false;

    // Estado inicial
    let isWidgetOpen = false;
    let isProcessing = false;
    let messageHistory = [];

    // Se não tiver acesso à IA, desabilita a entrada de texto e o botão de envio
    if (!hasAIAccess) {
        if (promptInput) {
            promptInput.disabled = true;
            promptInput.placeholder = "Acesso à IA não disponível no seu plano";
        }
        if (sendBtn) {
            sendBtn.disabled = true;
        }
        
        // Remove o botão de limpar histórico se existir
        const clearHistoryBtn = document.getElementById('clear-history');
        if (clearHistoryBtn) {
            clearHistoryBtn.style.display = 'none';
        }

        // Esconde a área de input
        const inputArea = document.querySelector('.ai-assistant-input');
        if (inputArea) {
            inputArea.style.display = 'none';
        }
    }

    // Funções de utilidade para o histórico
    function saveMessages(messages) {
        const today = new Date().toISOString().split('T')[0];
        localStorage.setItem(`chat_history_${today}`, JSON.stringify(messages));
    }

    function loadMessages() {
        const today = new Date().toISOString().split('T')[0];
        const savedMessages = localStorage.getItem(`chat_history_${today}`);
        return savedMessages ? JSON.parse(savedMessages) : [];
    }

    function isNewDay() {
        const lastMessage = messageHistory[messageHistory.length - 1];
        if (!lastMessage) return true;
        
        const lastMessageDate = new Date(lastMessage.timestamp).toISOString().split('T')[0];
        const today = new Date().toISOString().split('T')[0];
        return lastMessageDate !== today;
    }

    function cleanOldMessages() {
        const today = new Date().toISOString().split('T')[0];
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key.startsWith('chat_history_') && !key.includes(today)) {
                localStorage.removeItem(key);
            }
        }
    }

    function clearChatHistory() {
        const today = new Date().toISOString().split('T')[0];
        localStorage.removeItem(`chat_history_${today}`);
        messageHistory = [];
        messagesContainer.innerHTML = '';
        showWelcomeMessage();
    }

    function showWelcomeMessage() {
        addMessage('assistant', 'Olá! Sou o assistente virtual do Zaponto. Como posso ajudar você hoje?', false, true);
    }

    function loadChatHistory() {
        messageHistory = loadMessages();
        messagesContainer.innerHTML = '';
        
        if (messageHistory.length === 0) {
            showWelcomeMessage();
        } else {
            messageHistory.forEach(msg => {
                addMessage(msg.type, msg.content, false, false);
            });
        }
    }

    // Inicialização única do histórico
    cleanOldMessages();
    messageHistory = loadMessages();
    
    if (isNewDay() || messageHistory.length === 0) {
        clearChatHistory();
    } else {
        loadChatHistory();
    }

    function autoResizeTextarea(element) {
        element.style.height = 'auto';
        element.style.height = (element.scrollHeight) + 'px';
    }

    promptInput.addEventListener('input', function() {
        autoResizeTextarea(this);
    });

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

    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function addMessage(type, content, isLoading = false, isWelcome = false) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', `${type}-message`);
        
        const messageContent = document.createElement('div');
        messageContent.classList.add('message-content');

        if (!isLoading && !isWelcome) {
            const messageData = {
                type: type,
                content: content,
                timestamp: new Date().toISOString()
            };
            messageHistory.push(messageData);
            saveMessages(messageHistory);
        }

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
                    <span></span><span></span><span></span>
                </div>
            `;
        } else {
            messageBubble.innerHTML = content;
        }

        messageContent.appendChild(messageBubble);
        messageDiv.appendChild(messageContent);
        messagesContainer.appendChild(messageDiv);
        scrollToBottom();

        return messageDiv;
    }


    async function sendMessage() {
        if (isProcessing || !hasAIAccess) return;

        const prompt = promptInput.value.trim();
        if (!prompt) return;

        isProcessing = true;
        promptInput.value = '';
        autoResizeTextarea(promptInput);

        try {
            addMessage('user', prompt);
            const loadingMessage = addMessage('assistant', '', true);

            const response = await fetch('../pages/assistant_context_processor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ prompt: prompt })
            });

            loadingMessage.remove();

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data && data.success) {
                addMessage('assistant', data.content || data.message);
            } else {
                throw new Error(data.error || 'Erro desconhecido');
            }
        } catch (error) {
            console.error('Erro:', error);
            addMessage('assistant', 'Desculpe, ocorreu um erro ao processar sua mensagem. Por favor, tente novamente.');
        } finally {
            isProcessing = false;
            scrollToBottom();
        }
    }

    // Event listeners
    floatingButton.addEventListener('click', toggleWidget);
    toggleBtn.addEventListener('click', toggleWidget);
    sendBtn.addEventListener('click', sendMessage);
    promptInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    document.getElementById('clear-history').addEventListener('click', function(e) {
        e.stopPropagation();
        if (confirm('Tem certeza que deseja limpar o histórico de hoje?')) {
            clearChatHistory();
        }
    });

    document.addEventListener('click', (e) => {
        if (isWidgetOpen && !widget.contains(e.target) && !floatingButton.contains(e.target)) {
            toggleWidget();
        }
    });

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
            addMessage('assistant', 'Desculpe, o upload de arquivos ainda não está disponível.');
        }
    });
});

window.onerror = function(msg, url, lineNo, columnNo, error) {
    console.error('Erro:', {
        message: msg,
        url: url,
        lineNo: lineNo,
        columnNo: columnNo,
        error: error
    });
    return false;
};