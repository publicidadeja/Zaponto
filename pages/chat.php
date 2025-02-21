<?php
// Inicia a sessão, se ainda não estiver iniciada.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclui dependências.
require_once '../includes/db.php';
require_once '../includes/GeminiChat.php';

// Verifica se o usuário está logado. Redireciona para a página de login se não estiver.
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Chave API do Gemini (substitua pela sua chave real).  DEVE SER UMA VARIÁVEL DE AMBIENTE!
$apiKey = 'minha_api_aqui'; // ISSO É INSEGURO! USE VARIÁVEIS DE AMBIENTE!

// Inicializa o objeto GeminiChat com a conexão PDO, a chave API e o ID do usuário.
$chat = new GeminiChat($pdo, $apiKey, $_SESSION['usuario_id']);

// Processa a mensagem enviada pelo usuário.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    try {
        // Envia a mensagem para o Gemini e obtém a resposta.
        $response = $chat->sendMessage($_POST['message']);

        // Formata a resposta do Gemini para exibição no chat.
        $formattedResponse = formatGeminiResponse($response);

        // Retorna a resposta formatada como JSON.
        echo json_encode(['success' => true, 'message' => $formattedResponse]);
        exit;
    } catch (Exception $e) {
        // Em caso de erro, retorna uma mensagem de erro como JSON.
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

/**
 * Formata a resposta do Gemini para HTML.
 *
 * @param string $response A resposta original do Gemini.
 * @return string A resposta formatada em HTML.
 */
function formatGeminiResponse($response) {
    // 1. Títulos:  Substituir por tags HTML (h2, h3, etc.)
    $response = preg_replace('/^## (.*)$/m', '<h3>$1</h3>', $response);  // ## Título -> <h3>
    $response = preg_replace('/^# (.*)$/m', '<h2>$1</h2>', $response);   // # Título -> <h2>

    // 2. Negrito:  **texto**  ->  <strong>texto</strong>
    $response = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $response);

    // 3. Listas:
    $response = preg_replace('/^(\s*)- (.*)$/m', '$1<li>$2</li>', $response); // - item -> <li>
    $response = preg_replace('/^(<li>.*)(<li>.*)$/ms', '<ul>$1</ul>', $response); // envolve em <ul>

    //4. Quebras de linha:  \n  -> <br> (com cuidado)
    $response = nl2br($response, false); // Usa nl2br, mas com XHTML = false para <br> simples

    // 5. Espaçamento (CSS, não <br> em excesso)
    //    - Use CSS para margens e paddings entre parágrafos, títulos, listas, etc.

    // 6. Links:  Se o Gemini retornar links, já estarão em formato <a> (idealmente)

    // 7. Código (se houver):  ```  ->  <pre><code> (e escape HTML dentro!)
    $response = preg_replace_callback('/```(.*?)```/s', function($matches) {
        return '<pre><code>' . htmlspecialchars($matches[1]) . '</code></pre>';
    }, $response);

    return $response;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Assistente - Zaponto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Ícones (Material Symbols Outlined - Google Fonts) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        /* Variáveis de cor (fáceis de customizar) */
        :root {
            --primary-color: #0098fc; /* Azul do degradê */
            --secondary-color: #F5F7FA; /* Fundo claro */
            --accent-color: #FFD700; /* Dourado (opcional, para detalhes) */
            --text-color: #333;
            --light-text-color: #777;
            --bubble-user: #DCF8C6; /* Verde claro (balão do usuário) */
            --bubble-assistant: white;
            --border-radius: 20px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Fonte moderna */
            background-color: var(--secondary-color);
            margin: 0; /* Importante para ocupar a tela toda */
            padding: 0;
            overflow-x: hidden; /* Evita barra de rolagem horizontal */
        }

        .chat-widget {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 90%;
            max-width: 400px;
            height: 80vh;
            max-height: 650px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 1000;
            transition: transform 0.3s ease, height 0.3s ease, box-shadow 0.3s ease; /* Transições */
        }

        /* Efeito de hover no widget */
        .chat-widget:hover {
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3); /* Sombra mais intensa */
        }

        /* Cabeçalho */
        .chat-header {
            background: linear-gradient(135deg, #0098fc 0%, #0068b3 100%); /* Degradê */
            color: white;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: relative; /* Para o ícone */
        }

        /* Ícone da IA (dentro do cabeçalho) */
        .chat-header .ai-icon {
            position: absolute;
            left: 25px; /* Alinhado à esquerda */
            top: 50%;
            transform: translateY(-50%);
            width: 40px; /* Tamanho ajustável */
            height: 40px;
            border-radius: 50%; /* Circular */
            background-color: white; /* Fundo para o ícone */
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); /* Sombra */
            transition: transform 0.3s ease; /* Animação */
        }

        .chat-header .ai-icon img {
            width: 80%; /* Ajusta ao tamanho do container */
            height: 80%;
            object-fit: contain; /* Mantém proporção */
        }

        /* Efeito de pulsação no ícone */
        .chat-header .ai-icon:hover {
            transform: translateY(-50%) scale(1.1); /* Aumenta um pouco */
        }

        .chat-header h5 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
            flex-grow: 1;
            text-align: center;
            padding-left: 50px; /* Espaço para o ícone */
        }

        .chat-toggle-btn {
            cursor: pointer;
            background: transparent;
            border: none;
            color: white;
            font-size: 1.5rem;
            transition: transform 0.2s;
        }
        .chat-toggle-btn:hover {
            transform: scale(1.2);
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 25px;
            background: var(--secondary-color);
        }

        .message {
            margin-bottom: 20px;
            max-width: 80%;
            position: relative;
            clear: both;
            display: flex;
            flex-direction: column;
        }

        .user-message, .assistant-message {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            line-height: 1.4;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            word-wrap: break-word;  /* Quebra palavras longas */
        }

        .user-message {
            float: right;
            background: var(--bubble-user);
            color: var(--text-color);
            border-bottom-right-radius: 4px;
            margin-left: 20%;
        }

        .assistant-message {
            float: left;
            background: var(--bubble-assistant);
            color: var(--text-color);
            border-bottom-left-radius: 4px;
            margin-right: 20%;
        }

        /* Estilos para títulos, parágrafos, etc. dentro das mensagens */
        .assistant-message h2, .assistant-message h3 {
            margin-top: 0.5em;
            margin-bottom: 0.3em;
            font-weight: 600; /* Negrito para títulos */
        }
        .assistant-message p {
            margin-bottom: 0.8em; /* Espaçamento entre parágrafos */
        }
        .assistant-message ul {
            padding-left: 1.2em; /* Recuo da lista */
            margin-bottom: 0.8em;
        }
        .assistant-message li {
            margin-bottom: 0.3em; /* Espaçamento entre itens da lista */
        }
        .assistant-message pre { /* Para blocos de código */
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto; /* Barra de rolagem horizontal se necessário */
        }
        .assistant-message a { /* Links */
            color: #007bff;
            text-decoration: none;
        }
        .assistant-message a:hover {
            text-decoration: underline;
        }


        .message-time {
            font-size: 0.8rem;
            color: var(--light-text-color);
            margin-top: 8px;
            text-align: right;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .message:hover .message-time {
            opacity: 1;
        }

        .chat-input-container {
            padding: 20px;
            background: white;
            border-top: 1px solid #eee;
        }

        .chat-input-wrapper {
            display: flex;
            align-items: center;
            background: #f9f9f9;
            border-radius: 30px;
            padding: 8px 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .chat-input {
            flex: 1;
            border: none;
            padding: 12px 15px;
            background: transparent;
            outline: none;
            font-size: 1rem;
            color: var(--text-color);
        }

        .chat-input::placeholder {
            color: var(--light-text-color);
        }

        .send-button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 30px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .send-button:hover {
            background: #0068b3; /* Tom mais escuro (fim do degradê) */
            transform: translateY(-2px);
        }

        .typing-indicator {
            display: none;
            padding: 10px 15px;
            margin-left: 15px;
            margin-bottom: 10px;
        }

        .typing {
            display: flex;
            align-items: center;
        }

        .typing span {
            height: 10px;
            width: 10px;
            background: #bbb;
            border-radius: 50%;
            margin: 0 3px;
            display: inline-block;
            animation: bounce 1.4s infinite;
        }

        .typing span:nth-child(2) { animation-delay: 0.2s; }
        .typing span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes bounce {
            0%, 75%, 100% {
                transform: translateY(0);
            }
            25% {
                transform: translateY(-8px);
            }
        }

        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.3);
        }

        .chat-widget.minimized {
            height: 60px;
            overflow: hidden;
             /* Adiciona a animação de tremor */
            animation: shake 0.82s cubic-bezier(.36,.07,.19,.97) both;
            transform: translate3d(0, 0, 0);
            backface-visibility: hidden;
            perspective: 1000px;
        }
        .chat-widget.minimized .chat-messages,
        .chat-widget.minimized .chat-input-container {
            display: none;
        }

        /* Animação de tremor */
        @keyframes shake {
            10%, 90% {
                transform: translate3d(-1px, 0, 0);
            }
            20%, 80% {
                transform: translate3d(2px, 0, 0);
            }
            30%, 50%, 70% {
                transform: translate3d(-4px, 0, 0);
            }
            40%, 60% {
                transform: translate3d(4px, 0, 0);
            }
        }


        @media (max-width: 768px) {
            .chat-widget {
                width: 95%;
                right: 2.5%;
                bottom: 15px;
                height: 70vh;
            }
            .chat-messages {
                padding: 15px;
            }
            .message {
                max-width: 90%;
            }
            .user-message {
                margin-left: 10%;
            }
            .assistant-message {
                margin-right: 10%;
            }
             /* Esconde o ícone em telas menores */
            .chat-header .ai-icon {
                display: none;
            }
            .chat-header h5 {
                padding-left: 0; /* Remove o padding */
            }
        }

    </style>
</head>
<body>
    <div class="chat-widget minimized" id="chatWidget">  <!-- Inicialmente minimizado -->
        <div class="chat-header">
            <!-- Ícone da IA -->
            <div class="ai-icon">
                <img src="https://publicidadeja.com.br/wp-content/uploads/2025/02/icone-ai-zaponto.png" alt="Ícone IA">
            </div>
            <h5>Assistente Zaponto</h5>
            <!-- Botão de minimizar/maximizar -->
            <button class="chat-toggle-btn" id="chatToggleBtn" aria-label="Minimizar/Maximizar">
                <span class="material-symbols-outlined">expand_less</span> <!-- Começa com expand_less -->
            </button>
        </div>
        <div class="chat-messages" id="chatMessages">
            <!-- Mensagem de boas-vindas -->
            <div class="message assistant-message">
                Olá! Sou especialista em marketing do seu negócio. Como posso ajudar você hoje?
                <div class="message-time">Agora</div>
            </div>
        </div>
        <div class="typing-indicator" id="typingIndicator">
            <div class="typing">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        <div class="chat-input-container">
            <div class="chat-input-wrapper">
                <input type="text" id="messageInput" class="chat-input" placeholder="Digite sua mensagem...">
                <button id="sendButton" class="send-button">
                    Enviar
                    <span class="material-symbols-outlined">send</span>
                </button>
            </div>
        </div>
    </div>

    <script>
    const messageInput = document.getElementById('messageInput');
    const sendButton = document.getElementById('sendButton');
    const chatMessages = document.getElementById('chatMessages');
    const typingIndicator = document.getElementById('typingIndicator');
    const chatWidget = document.getElementById('chatWidget');
    const chatToggleBtn = document.getElementById('chatToggleBtn');

    let isChatMinimized = true; // Começa minimizado

    /**
     * Mostra o indicador de digitação.
     */
    function showTypingIndicator() {
        typingIndicator.style.display = 'block';
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    /**
     * Oculta o indicador de digitação.
     */
    function hideTypingIndicator() {
        typingIndicator.style.display = 'none';
    }

    /**
     * Formata a hora atual no formato HH:MM.
     *
     * @returns {string} A hora formatada.
     */
    function formatTime() {
        const now = new Date();
        return now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

   /**
     * Adiciona uma mensagem ao chat.
     *
     * @param {string} message O conteúdo da mensagem.
     * @param {string} type O tipo de mensagem ('user' ou 'assistant').
     */
    function addMessage(message, type) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}-message`; // Aplica a classe CSS correta

        messageDiv.innerHTML = `
            ${message}
            <div class="message-time">${formatTime()}</div>
        `;

        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    /**
     * Envia a mensagem do usuário para o servidor e processa a resposta.
     */
    async function sendMessage() {
        const message = messageInput.value.trim();
        if (!message) return;

        messageInput.value = '';
        addMessage(message, 'user'); // Adiciona a mensagem do usuário imediatamente
        showTypingIndicator();

        try {
            const response = await fetch('chat.php', { // Mesmo arquivo, mas agora com o script PHP no topo
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `message=${encodeURIComponent(message)}`
            });

            const data = await response.json();
            hideTypingIndicator();

            if (data.success) {
                addMessage(data.message, 'assistant'); // Adiciona a resposta do assistente
            } else {
                addMessage('Desculpe, ocorreu um erro ao processar sua mensagem.', 'assistant');
            }
        } catch (error) {
            hideTypingIndicator();
            console.error('Erro detalhado:', error);
            addMessage('Erro ao enviar mensagem: ' + error.message, 'assistant');
        }
    }

    /**
 * Carrega o histórico de chat do servidor.
 */
    async function loadChatHistory() {
        try {
            const response = await fetch('get_chat_history.php');
            const data = await response.json();

            if (data.success && Array.isArray(data.data)) {
                data.data.forEach(msg => {
                    // CORREÇÃO: Usa msg.sender para determinar o tipo da mensagem
                    addMessage(msg.mensagem, msg.sender);
                });
            } else {
                console.warn('Nenhuma mensagem no histórico ou erro:', data.error);
            }
        } catch (error) {
            console.error('Erro ao carregar histórico:', error);
        }
    }

    // Event listeners
    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    chatToggleBtn.addEventListener('click', () => {
        isChatMinimized = !isChatMinimized;
        chatWidget.classList.toggle('minimized', isChatMinimized);
        chatToggleBtn.querySelector('span').textContent = isChatMinimized ? 'expand_less' : 'expand_more';

        //  Remove a animação de tremor após a primeira interação
        if (!isChatMinimized) {
            chatWidget.style.animation = '';
        }
    });

    // Carrega o histórico quando o documento estiver pronto
    document.addEventListener('DOMContentLoaded', function() {
        loadChatHistory(); // Carrega o histórico quando a página abrir
    });

    </script>
</body>
</html>