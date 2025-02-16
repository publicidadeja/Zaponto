document.addEventListener('DOMContentLoaded', function() {
    // Elementos DOM
    const widget = document.getElementById('ai-assistant-widget');
    const floatingButton = document.getElementById('ai-assistant-floating-button');
    const messagesContainer = document.getElementById('ai-assistant-messages');
    const promptInput = document.getElementById('ai-assistant-prompt');
    const sendBtn = document.getElementById('ai-assistant-send');
    const toggleBtn = document.getElementById('ai-assistant-toggle');

    // Carrega o histórico de mensagens
    loadChatHistory();
    
    // Estado inicial
    let isWidgetOpen = false;
    let isProcessing = false;

    document.getElementById('clear-history').addEventListener('click', function(e) {
        e.stopPropagation(); // Evita que o chat feche ao clicar no botão
        if (confirm('Tem certeza que deseja limpar o histórico de hoje?')) {
            clearChatHistory();
        }
    });

    // Função para salvar mensagens no localStorage
function saveMessages(messages) {
    const today = new Date().toISOString().split('T')[0]; // Pega apenas a data
    localStorage.setItem(`chat_history_${today}`, JSON.stringify(messages));
}

// Função para carregar mensagens do localStorage
function loadMessages() {
    const today = new Date().toISOString().split('T')[0];
    const savedMessages = localStorage.getItem(`chat_history_${today}`);
    return savedMessages ? JSON.parse(savedMessages) : [];
}

// Função para limpar histórico de mensagens
function clearChatHistory() {
    const today = new Date().toISOString().split('T')[0];
    localStorage.removeItem(`chat_history_${today}`);
    messageHistory = [];
    messagesContainer.innerHTML = '';
    addMessage('assistant', 'Olá! Sou o assistente virtual do Zaponto. Como posso ajudar você hoje?');
}

// Array para manter o histórico de mensagens em memória
let messageHistory = loadMessages();

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

    function addMessage(type, content) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}-message`;
        
        const messageContent = document.createElement('div');
        messageContent.className = 'message-content';
        
        if (type === 'assistant') {
            const avatar = document.createElement('img');
            avatar.src = 'https://publicidadeja.com.br/wp-content/uploads/2025/02/icone-ai-zaponto.png';
            avatar.className = 'assistant-avatar';
            messageContent.appendChild(avatar);
        }
        
        const messageBubble = document.createElement('div');
        messageBubble.className = 'message-bubble';
        messageBubble.innerHTML = content;
        
        messageContent.appendChild(messageBubble);
        messageDiv.appendChild(messageContent);
        messagesContainer.appendChild(messageDiv);
        
        // Adiciona a mensagem ao histórico
        messageHistory.push({ type, content });
        
        // Salva o histórico atualizado
        saveMessages(messageHistory);
        
        // Rola para a última mensagem
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
            messageBubble.innerHTML = content;
            // Adiciona a mensagem ao histórico apenas se não for loading
            messageHistory.push({
                type: type,
                content: content,
                timestamp: new Date().toISOString()
            });
            // Salva o histórico atualizado
            saveMessages(messageHistory);
        }
    
        messageContent.appendChild(messageBubble);
        messageDiv.appendChild(messageContent);
        messagesContainer.appendChild(messageDiv);
        scrollToBottom();
    
        return messageDiv;
    }
    
    // Adicione esta função para carregar o histórico quando a página for carregada
    function loadChatHistory() {
        messageHistory = loadMessages(); // Carrega as mensagens do localStorage
        
        // Limpa o container de mensagens
        messagesContainer.innerHTML = '';
        
        // Se não houver mensagens, adiciona a mensagem de boas-vindas
        if (messageHistory.length === 0) {
            addMessage('assistant', 'Olá! Sou o assistente virtual do Zaponto. Como posso ajudar você hoje?');
        } else {
            // Adiciona todas as mensagens do histórico
            messageHistory.forEach(msg => {
                addMessage(msg.type, msg.content);
            });
        }
    }

    async function checkSession() {
    try {
        const response = await fetch('check_session.php');
        const data = await response.json();
        if (!data.authenticated) {
            window.location.href = 'login.php';
        }
    } catch (error) {
        console.error('Erro ao verificar sessão:', error);
    }
}

document.addEventListener('DOMContentLoaded', checkSession);

    // Enviar mensagem
    async function sendMessage() {
        if (isProcessing) return;
    
        const prompt = promptInput.value.trim();
        if (!prompt) return;
    
        isProcessing = true;
        promptInput.value = '';
        autoResizeTextarea(promptInput);
    
        try {
            // Adiciona a mensagem do usuário
            addMessage('user', prompt);
            // Usa addMessage com parâmetro isLoading como true para mostrar loading
            const loadingMessage = addMessage('assistant', '', true);
    
            const response = await fetch('../pages/assistant_context_processor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ prompt: prompt })
            });
    
            // Remove a mensagem de loading
            loadingMessage.remove();
    
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Erro na resposta:', errorText);
                throw new Error(`HTTP error! status: ${response.status}, body: ${errorText}`);
            }
    
            const data = await response.json();
    
            if (data && data.success) {
                addMessage('assistant', data.content || data.message);
            } else {
                throw new Error(data.error || 'Erro desconhecido');
            }
        } catch (error) {
            console.error('Erro detalhado:', error);
            addMessage('assistant', 'Desculpe, ocorreu um erro ao processar sua mensagem. Por favor, tente novamente.');
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