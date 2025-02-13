<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Processar requisição AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $api_key = 'YOUR_ANTHROPIC_API_KEY';
    $api_url = 'https://api.anthropic.com/v1/messages';

    try {
        $raw_data = file_get_contents('php://input');
        $data = json_decode($raw_data, true);
        
        if (!isset($data['message'])) {
            throw new Exception('Message is required');
        }

        $client = new GuzzleHttp\Client();
        $response = $client->post($api_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'Anthropic-Version' => '2023-06-01'
            ],
            'json' => [
                'model' => 'claude-3-haiku-20240307',
                'max_tokens' => 1000,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $data['message']
                    ]
                ]
            ]
        ]);

        $result = json_decode($response->getBody(), true);
        echo json_encode(['success' => true, 'message' => $result['content'][0]['text']]);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Assistant</title>
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1d4ed8;
            --bg-color: #ffffff;
            --text-color: #1e293b;
            --border-color: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: transparent;
        }

        #chatWidget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        .chat-bubble {
            width: 60px;
            height: 60px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: transform 0.3s ease;
        }

        .chat-bubble:hover {
            transform: scale(1.1);
        }

        .chat-bubble img {
            width: 30px;
            height: 30px;
            filter: brightness(0) invert(1);
        }

        .chat-container {
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 350px;
            height: 500px;
            background: var(--bg-color);
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: none;
            flex-direction: column;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .chat-container.active {
            display: flex;
        }

        .chat-header {
            padding: 16px;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title img {
            width: 24px;
            height: 24px;
            filter: brightness(0) invert(1);
        }

        .close-chat {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 20px;
        }

        .chat-messages {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background: #f8fafc;
        }

        .message {
            margin-bottom: 12px;
            max-width: 80%;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.4;
        }

        .message.user {
            background: var(--primary-color);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }

        .message.assistant {
            background: white;
            color: var(--text-color);
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .chat-input {
            padding: 16px;
            background: white;
            border-top: 1px solid var(--border-color);
        }

        .input-container {
            display: flex;
            gap: 8px;
        }

        #messageInput {
            flex: 1;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            resize: none;
            font-family: inherit;
        }

        #sendMessage {
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        #sendMessage:hover {
            background: var(--secondary-color);
        }

        .typing-indicator {
            padding: 10px;
            background: white;
            border-radius: 8px;
            display: none;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #64748b;
        }

        @media (max-width: 480px) {
            .chat-container {
                width: calc(100% - 40px);
                height: 60vh;
            }
        }
    </style>
</head>
<body>
    <div id="chatWidget">
        <div class="chat-bubble" id="chatBubble">
            <img src="<?php echo getBaseUrl(); ?>/public/img/assistant-icon.png" alt="Chat">
        </div>
        
        <div class="chat-container" id="chatContainer">
            <div class="chat-header">
                <div class="header-title">
                    <img src="<?php echo getBaseUrl(); ?>/public/img/assistant-icon.png" alt="Assistant">
                    <span>Marketing Assistant</span>
                </div>
                <button class="close-chat" id="closeChat">&times;</button>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <div class="message assistant">
                    Olá! Como posso ajudar você hoje?
                </div>
                <div class="typing-indicator" id="typingIndicator">
                    Assistente está digitando...
                </div>
            </div>
            
            <div class="chat-input">
                <div class="input-container">
                    <textarea 
                        id="messageInput" 
                        placeholder="Digite sua mensagem..."
                        rows="1"
                    ></textarea>
                    <button id="sendMessage">Enviar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        class ChatAssistant {
            constructor() {
                this.initializeElements();
                this.bindEvents();
                this.isTyping = false;
            }

            initializeElements() {
                this.chatBubble = document.getElementById('chatBubble');
                this.chatContainer = document.getElementById('chatContainer');
                this.closeButton = document.getElementById('closeChat');
                this.messagesContainer = document.getElementById('chatMessages');
                this.messageInput = document.getElementById('messageInput');
                this.sendButton = document.getElementById('sendMessage');
                this.typingIndicator = document.getElementById('typingIndicator');
            }

            bindEvents() {
                this.chatBubble.addEventListener('click', () => this.toggleChat());
                this.closeButton.addEventListener('click', () => this.toggleChat());
                this.sendButton.addEventListener('click', () => this.sendMessage());
                this.messageInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.sendMessage();
                    }
                });
            }

            toggleChat() {
                this.chatContainer.classList.toggle('active');
                if (this.chatContainer.classList.contains('active')) {
                    this.messageInput.focus();
                }
            }

            async sendMessage() {
                const message = this.messageInput.value.trim();
                if (!message || this.isTyping) return;

                this.addMessage(message, 'user');
                this.messageInput.value = '';
                this.isTyping = true;
                this.typingIndicator.style.display = 'block';

                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ message })
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        this.addMessage(data.message, 'assistant');
                    } else {
                        throw new Error(data.error || 'Erro ao processar mensagem');
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    this.addMessage('Desculpe, ocorreu um erro ao processar sua mensagem.', 'assistant');
                } finally {
                    this.isTyping = false;
                    this.typingIndicator.style.display = 'none';
                }
            }

            addMessage(message, type) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${type}`;
                messageDiv.textContent = message;
                this.messagesContainer.appendChild(messageDiv);
                this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
            }
        }

        // Inicializar o chat quando o documento estiver pronto
        document.addEventListener('DOMContentLoaded', () => {
            new ChatAssistant();
        });
    </script>
</body>
</html>