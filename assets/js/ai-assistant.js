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