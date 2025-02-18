// ../assets/js/ai-assistant.js

document.addEventListener('DOMContentLoaded', function () {

    // -- Constantes e Variáveis Globais --
    const API_ENDPOINT = '../pages/assistant_context_processor.php';
    const HISTORY_ENDPOINT = '../includes/assistant.php';
    const ICON_URL = 'https://publicidadeja.com.br/wp-content/uploads/2025/02/icone-ai-zaponto.png'; // URL do ícone

    // Elementos DOM (selecionados uma vez)
    const widget = document.getElementById('ai-assistant-widget');
    const floatingButton = document.getElementById('ai-assistant-floating-button');
    const messagesContainer = document.getElementById('ai-assistant-messages');
    const promptInput = document.getElementById('ai-assistant-prompt');
    const sendBtn = document.getElementById('ai-assistant-send');
    const toggleBtn = document.getElementById('ai-assistant-toggle');
    const clearHistoryBtn = document.getElementById('clear-history'); // Botão de limpar histórico

    // Estado do Widget
    let isWidgetOpen = false;
    let isProcessing = false;

    // -- Inicialização --
    initializeChat();

    async function initializeChat() {
        try {
            await loadChatHistory(); // Carrega o histórico
        } catch (error) {
            console.error('Erro ao inicializar o chat:', error);
            showWelcomeMessage(); // Mostra mensagem de boas-vindas em caso de erro
        }
        setupUI(); // Configura a interface
    }

    function setupUI() {
        // Desabilita input e botão se não tiver acesso à IA
        if (!window.hasAIAccess) {
            promptInput.disabled = true;
            promptInput.placeholder = "Acesso à IA não disponível no seu plano.";
            sendBtn.disabled = true;
            if (clearHistoryBtn) {
                clearHistoryBtn.style.display = 'none'; // Esconde o botão de limpar
            }
            // Esconde a área de input
            const inputArea = document.querySelector('.ai-assistant-input');
            if (inputArea) {
                inputArea.style.display = 'none';
            }
        }
    }

    // -- Funções Auxiliares --

    // Cria um elemento de mensagem (refatorada para maior flexibilidade)
    function createMessageElement(type, content, isLoading = false) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', type === 'user' ? 'user-message' : 'assistant-message');

        const messageContent = document.createElement('div');
        messageContent.classList.add('message-content');

        if (type !== 'user') {
            const avatar = document.createElement('img');
            avatar.src = ICON_URL;
            avatar.classList.add('assistant-avatar');
            messageContent.appendChild(avatar);
        }

        const messageBubble = document.createElement('div');
        messageBubble.classList.add('message-bubble');

        if (isLoading) {
            messageBubble.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
        } else {
            messageBubble.textContent = content; // Usa textContent para segurança
        }

        messageContent.appendChild(messageBubble);
        messageDiv.appendChild(messageContent);
        return messageDiv;
    }

    // Adiciona uma mensagem à interface (simplificada)
    function addMessageToUI(type, content, isLoading = false) {
        const messageElement = createMessageElement(type, content, isLoading);
        messagesContainer.appendChild(messageElement);
        scrollToBottom(); // Rola para baixo
    }

    // Mostra a mensagem de boas-vindas
    function showWelcomeMessage() {
        if (window.hasAIAccess) {
            addMessageToUI('assistant', 'Olá! Sou o especialista de marketing do Zaponto. Como posso ajudar você hoje?');
        }
    }

    // Rola a área de mensagens para baixo
    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Redimensiona o textarea automaticamente
    function autoResizeTextarea() {
        promptInput.style.height = 'auto';
        promptInput.style.height = promptInput.scrollHeight + 'px';
    }

    // Alterna a visibilidade do widget
    function toggleWidget() {
        isWidgetOpen = !isWidgetOpen;
        widget.classList.toggle('ai-assistant-closed', !isWidgetOpen);
        floatingButton.style.display = isWidgetOpen ? 'none' : 'flex';
        if (isWidgetOpen) {
            promptInput.focus();
            scrollToBottom();
        }
    }

    // -- Funções de Histórico (AJAX) --

    // Função genérica para requisições AJAX (refatorada)
    async function makeRequest(url, data) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Erro na requisição: ${response.status} - ${errorText}`);
            }

            const result = await response.json();
            if (!result.success) {
                throw new Error(result.error || 'Erro desconhecido na resposta.');
            }

            return result; // Retorna os dados em caso de sucesso

        } catch (error) {
            console.error('Erro na requisição:', error);
            throw error; // Propaga o erro
        }
    }

    // Salva uma mensagem
    async function saveMessage(message, type) {
        return makeRequest(HISTORY_ENDPOINT, { action: 'save', message, type });
    }

    // Carrega o histórico do chat
    async function loadChatHistory() {
        const result = await makeRequest(HISTORY_ENDPOINT, { action: 'load' });
        if (result && result.messages) {
            messagesContainer.innerHTML = ''; // Limpa o container
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
        }
    }

    // Limpa o histórico do chat
    async function clearChatHistory() {
        try {
            await makeRequest(HISTORY_ENDPOINT, { action: 'clear' });
            messagesContainer.innerHTML = ''; // Limpa o container
            showWelcomeMessage();
        } catch (error) {
            console.error('Erro ao limpar o histórico:', error); // Já tratado no makeRequest
        }
    }

    // -- Função Principal de Envio de Mensagem --

    async function sendMessage() {
        if (isProcessing || !window.hasAIAccess) return;

        const prompt = promptInput.value.trim();
        if (!prompt) return;

        isProcessing = true;
        promptInput.value = '';
        autoResizeTextarea();

        addMessageToUI('user', prompt);
        const loadingMessage = addMessageToUI('assistant', '', true);

        try {
            const data = await makeRequest(API_ENDPOINT, { prompt }); // Usa a função makeRequest

            const aiResponse = data.content || 'Desculpe, não consegui entender.'; // Mensagem padrão
            loadingMessage.remove();
            addMessageToUI('assistant', aiResponse);

            // Salva as mensagens (usuário e assistente)
            await saveMessage(prompt, 'user');
            await saveMessage(aiResponse, 'assistant');

        } catch (error) {
            loadingMessage.remove();
            addMessageToUI('assistant', 'Desculpe, ocorreu um erro ao processar sua mensagem.'); // Mensagem genérica
            // Erro já tratado no makeRequest
        } finally {
            isProcessing = false;
            scrollToBottom();
        }
    }

    // -- Event Listeners --

    // Clique no botão flutuante
    floatingButton.addEventListener('click', toggleWidget);

    // Clique no botão de fechar
    toggleBtn.addEventListener('click', toggleWidget);

    // Clique no botão de enviar
    sendBtn.addEventListener('click', sendMessage);

    // Pressionar Enter no textarea
    promptInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    promptInput.addEventListener('input', autoResizeTextarea); //Redimensiona ao digitar

    // Clique no botão de limpar histórico
    if (clearHistoryBtn) { // Verifica se o botão existe
        clearHistoryBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (confirm('Tem certeza que deseja limpar o histórico de hoje?')) {
                clearChatHistory();
            }
        });
    }

    // Fechar o widget ao clicar fora
    document.addEventListener('click', (e) => {
        if (isWidgetOpen && !widget.contains(e.target) && !floatingButton.contains(e.target)) {
            toggleWidget();
        }
    });

    // Tratamento de Drag and Drop (opcional)
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

    // Tratamento de Erros Global (opcional, mas recomendado)
    window.onerror = function (msg, url, lineNo, columnNo, error) {
        console.error('Erro global:', { message: msg, url: url, lineNo: lineNo, columnNo: columnNo, error: error });
        return false; // Impede o comportamento padrão do navegador
    };
});