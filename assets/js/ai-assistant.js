document.addEventListener('DOMContentLoaded', function () {
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

    // Inicializa o chat
    initializeChat();

    async function initializeChat() {
        try {
            await loadChatHistory();
        } catch (error) {
            console.error('Erro ao inicializar chat:', error);
            showWelcomeMessage(); // Mostra mensagem de boas-vindas em caso de erro
        }
        setupUI(); // Configura a UI depois de carregar o histórico (ou em caso de erro)
    }

    function setupUI() {
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
    }

    // Funções auxiliares para adicionar mensagens (refatoradas)
    function createMessageElement(type, content, isLoading = false) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message');
        messageDiv.classList.add(type === 'user' ? 'user-message' : 'assistant-message');

        const messageContent = document.createElement('div');
        messageContent.classList.add('message-content');

        if (type !== 'user') {
            const avatar = document.createElement('img');
            avatar.src = 'https://publicidadeja.com.br/wp-content/uploads/2025/02/icone-ai-zaponto.png';
            avatar.classList.add('assistant-avatar');
            messageContent.appendChild(avatar); // Adiciona o avatar diretamente ao messageContent
        }

        const messageBubble = document.createElement('div');
        messageBubble.classList.add('message-bubble');

        if (isLoading) {
            messageBubble.textContent = ''; // Mantém o bubble vazio, mas com a estrutura
            const typingIndicator = document.createElement('div');
            typingIndicator.classList.add('typing-indicator');
            typingIndicator.innerHTML = '<span></span><span></span><span></span>'; // Adiciona o indicador como innerHTML
            messageBubble.appendChild(typingIndicator);

        } else {
            messageBubble.textContent = content; // Usa textContent para segurança
        }

        messageContent.appendChild(messageBubble);
        messageDiv.appendChild(messageContent);
        return messageDiv;
    }

    function addMessageToUI(type, content, isLoading = false) {
        const messageElement = createMessageElement(type, content, isLoading);
        messagesContainer.appendChild(messageElement);
        scrollToBottom();
        return messageElement;
    }


    // Funções de histórico (AJAX)
    async function saveMessage(message, type) {
        try {
            const response = await fetch('../includes/assistant.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'save',
                    message: message,
                    type: type
                })
            });
            const result = await response.json();
            if (!result.success) {
                console.error('Erro ao salvar mensagem:', result.error);
            }
            return result; // Retorna o resultado para possível uso futuro
        } catch (error) {
            console.error('Erro ao salvar mensagem:', error);
            return { success: false, error: error.message }; // Retorna um objeto de erro
        }
    }


    async function loadChatHistory() {
        try {
            const response = await fetch('../includes/assistant.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'load' })
            });

            const result = await response.json();

            if (result && result.success && Array.isArray(result.messages)) {
                messagesContainer.innerHTML = ''; // Limpa o container antes de adicionar as mensagens

                if (result.messages.length === 0) {
                    showWelcomeMessage();
                } else {
                    result.messages.forEach(msg => {
                        const messageType = (msg.tipo_mensagem || msg.type || '').toLowerCase();
                        if (['user', 'assistant'].includes(messageType) && msg.mensagem) {
                            addMessageToUI(messageType, msg.mensagem);
                        }
                    });
                }
            } else {
                console.error('Erro ao carregar mensagens:', result);
                showWelcomeMessage(); // Mostra mensagem de boas-vindas em caso de erro
            }
        } catch (error) {
            console.error('Erro ao carregar histórico:', error);
            showWelcomeMessage(); // Mostra mensagem de boas-vindas em caso de erro
        }
    }


    async function clearChatHistory() {
        try {
            const response = await fetch('../includes/assistant.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'clear' })
            });

            if (response.ok) {
                messagesContainer.innerHTML = ''; // Limpa o container
                showWelcomeMessage();
            } else {
                console.error('Erro ao limpar histórico:', await response.text()); // Log do erro
            }
        } catch (error) {
            console.error('Erro ao limpar histórico:', error);
        }
    }

    function showWelcomeMessage() {
        if (hasAIAccess) {
            addMessageToUI('assistant', 'Olá! Sou o especialista de marketing do Zaponto. Como posso ajudar você hoje?');
        } // Não precisa mais do else, pois a mensagem de "sem acesso" é mostrada estaticamente no HTML
    }


    function autoResizeTextarea(element) {
        element.style.height = 'auto';
        element.style.height = (element.scrollHeight) + 'px';
    }

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

    async function sendMessage() {
        if (isProcessing || !hasAIAccess) return;

        const prompt = promptInput.value.trim();
        if (!prompt) return;

        isProcessing = true;
        promptInput.value = '';
        autoResizeTextarea(promptInput);

        addMessageToUI('user', prompt); // Adiciona a mensagem do usuário imediatamente
        const loadingMessage = addMessageToUI('assistant', '', true); // Adiciona o indicador de carregamento

        try {
            const response = await fetch('../pages/assistant_context_processor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ prompt: prompt })
            });


            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data && data.success) {
                const aiResponse = data.content || data.message; // Pega a resposta da API
                loadingMessage.remove(); // Remove o indicador de carregamento
                addMessageToUI('assistant', aiResponse); // Adiciona a resposta da IA

                // Salva AMBAS as mensagens (usuário e assistente) AGORA
                await saveMessage(prompt, 'user');
                await saveMessage(aiResponse, 'assistant');

            } else {
                loadingMessage.remove(); // Remove o indicador de carregamento
                addMessageToUI('assistant', 'Desculpe, ocorreu um erro ao processar sua mensagem.  A resposta da API não foi bem-sucedida.'); // Mensagem de erro mais específica
                throw new Error(data.error || 'Erro desconhecido');
            }

        } catch (error) {
            console.error('Erro:', error);
            loadingMessage.remove(); // Remove o indicador de carregamento em caso de erro
            addMessageToUI('assistant', 'Desculpe, ocorreu um erro ao processar sua mensagem. Por favor, tente novamente.'); // Mensagem de erro genérica
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

    document.getElementById('clear-history').addEventListener('click', function (e) {
        e.stopPropagation(); // Impede que o evento de clique se propague para o documento
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
            addMessageToUI('assistant', 'Desculpe, o upload de arquivos ainda não está disponível.');
        }
    });

    // Tratamento de erros global (opcional, mas recomendado)
    window.onerror = function (msg, url, lineNo, columnNo, error) {
        console.error('Erro:', { message: msg, url: url, lineNo: lineNo, columnNo: columnNo, error: error });
        return false; // Impede que o erro seja exibido no console do navegador (mas ainda o registra)
    };
});