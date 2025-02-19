<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/GeminiChat.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Sua chave API do Gemini
$apiKey = 'minha_api_aqui';

// Inicializa o chat
$chat = new GeminiChat($pdo, $apiKey, $_SESSION['usuario_id']);

// Processa mensagem enviada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    try {
        $response = $chat->sendMessage($_POST['message']);
        echo json_encode(['success' => true, 'message' => $response]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Assistente - ZapLocal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .chat-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 380px;
            height: 600px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 1000;
        }

        .chat-header {
            background: #0098fc;
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-header h5 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 15px;
            max-width: 85%;
            position: relative;
            clear: both;
        }

        .user-message {
            float: right;
            background: #0098fc;
            color: white;
            padding: 12px 15px;
            border-radius: 15px 15px 0 15px;
            margin-left: 15%;
        }

        .assistant-message {
            float: left;
            background: white;
            color: #333;
            padding: 12px 15px;
            border-radius: 15px 15px 15px 0;
            margin-right: 15%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .message-time {
            font-size: 0.7rem;
            color: #888;
            margin-top: 5px;
            text-align: right;
        }

        .chat-input-container {
            padding: 15px;
            background: white;
            border-top: 1px solid #eee;
        }

        .chat-input-wrapper {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border-radius: 25px;
            padding: 5px;
        }

        .chat-input {
            flex: 1;
            border: none;
            padding: 10px 15px;
            background: transparent;
            outline: none;
            font-size: 0.95rem;
        }

        .send-button {
            background: #0098fc;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .send-button:hover {
            background: #0084db;
        }

        .typing-indicator {
            display: none;
            padding: 10px;
            color: #666;
            font-style: italic;
        }

        /* Scrollbar personalizada */
        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Animação de digitação */
        .typing {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .typing span {
            height: 8px;
            width: 8px;
            background: #93959f;
            border-radius: 50%;
            margin: 0 2px;
            display: inline-block;
            animation: bounce 1.3s linear infinite;
        }

        .typing span:nth-child(2) { animation-delay: 0.16s; }
        .typing span:nth-child(3) { animation-delay: 0.32s; }

        @keyframes bounce {
            0%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <div class="chat-widget">
        <div class="chat-header">
            <h5>Assistente Virtual ZapLocal</h5>
            <button class="btn-close btn-close-white" aria-label="Minimizar"></button>
        </div>
        <div class="chat-messages" id="chatMessages">
            <!-- Mensagem de boas-vindas -->
            <div class="message assistant-message">
                Olá! Sou seu assistente virtual. Como posso ajudar você hoje?
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
                <button id="sendButton" class="send-button">Enviar</button>
            </div>
        </div>
    </div>

    <script>
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const chatMessages = document.getElementById('chatMessages');
        const typingIndicator = document.getElementById('typingIndicator');

        function showTypingIndicator() {
            typingIndicator.style.display = 'block';
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function hideTypingIndicator() {
            typingIndicator.style.display = 'none';
        }

        function formatTime() {
            const now = new Date();
            return now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        }

        function addMessage(message, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}-message`;
            messageDiv.innerHTML = `
                ${message}
                <div class="message-time">${formatTime()}</div>
            `;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        async function sendMessage() {
            const message = messageInput.value.trim();
            if (!message) return;

            // Limpa input e adiciona mensagem do usuário
            messageInput.value = '';
            addMessage(message, 'user');
            
            // Mostra indicador de digitação
            showTypingIndicator();

            try {
                const response = await fetch('chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `message=${encodeURIComponent(message)}`
                });

                const data = await response.json();
                
                // Esconde indicador de digitação
                hideTypingIndicator();

                if (data.success) {
                    addMessage(data.message, 'assistant');
                } else {
                    addMessage('Desculpe, ocorreu um erro ao processar sua mensagem.', 'assistant');
                }
            } catch (error) {
    hideTypingIndicator();
    console.error('Erro detalhado:', error);
    addMessage('Erro ao enviar mensagem: ' + error.message, 'assistant');
}
        }

        // Event Listeners
        sendButton.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // Minimizar/Maximizar chat
        document.querySelector('.btn-close').addEventListener('click', () => {
            const chatWidget = document.querySelector('.chat-widget');
            if (chatWidget.style.height === '600px' || !chatWidget.style.height) {
                chatWidget.style.height = '50px';
                chatMessages.style.display = 'none';
                document.querySelector('.chat-input-container').style.display = 'none';
            } else {
                chatWidget.style.height = '600px';
                chatMessages.style.display = 'block';
                document.querySelector('.chat-input-container').style.display = 'block';
            }
        });
    </script>
</body>
</html>