<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Processar requisição AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $api_key = 'YOUR_ANTHROPIC_API_KEY'; // Substitua pela sua chave API
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
    <title>Marketing Assistant</title>
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1d4ed8;
            --bg-color: #f8fafc;
            --text-color: #1e293b;
        }

        .assistant-bubble {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 9999;
        }

        .bubble-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .bubble-button img {
            width: 35px;
            height: 35px;
            object-fit: cover;
        }

        .bubble-button:hover {
            transform: scale(1.1);
            background: var(--secondary-color);
        }

        .chat-container {
            position: fixed;
            bottom: 90px;
            left: 20px;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
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
            gap: 12px;
        }

        .chat-header img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }

        .chat-messages {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background: var(--bg-color);
        }

        .message {
            margin: 8px 0;
            padding: 12px;
            border-radius: 12px;
            max-width: 85%;
            font-size: 14px;
            line-height: 1.4;
        }

        .message.user {
            background: var(--primary-color);
            color: white;
            margin-left: auto;
        }

        .message.assistant {
            background: white;
            color: var(--text-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .chat-input {
            padding: 16px;
            background: white;
            border-top: 1px solid #e2e8f0;
        }

        #userMessage {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            resize: none;
            font-size: 14px;
            margin-bottom: 8px;
        }

        #sendMessage {
            width: 100%;
            padding: 10px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        #sendMessage:hover {
            background: var(--secondary-color);
        }

        .typing-indicator {
            padding: 12px;
            background: white;
            border-radius: 8px;
            display: none;
            font-size: 14px;
            color: #64748b;
        }

        @media (max-width: 480px) {
            .chat-container {
                width: calc(100% - 40px);
                height: 60vh;
                bottom: 100px;
            }
        }
    </style>
</head>
<body>

<div class="assistant-bubble">
    <div class="bubble-button" id="bubbleButton">
        <img src="<?php echo getBaseUrl(); ?>/public/img/assistant-icon.png" alt="Assistant">
    </div>
    
    <div class="chat-container" id="chatContainer">
        <div class="chat-header">
            <img src="<?php echo getBaseUrl(); ?>/public/img/assistant-icon.png" alt="Assistant">
            <h4>Marketing Assistant</h4>
        </div>
        
        <div class="chat-messages" id="chatMessages">
            <div class="message assistant">
                Olá! Sou seu assistente de marketing. Como posso ajudar você hoje?
            </div>
            <div class="typing-indicator" id="typingIndicator">
                Assistente está digitando...
            </div>
        </div>
        
        <div class="chat-input">
            <textarea 
                id="userMessage" 
                placeholder="Digite sua mensagem..." 
                rows="3"
            ></textarea>
            <button id="sendMessage">Enviar</button>
        </div>
    </div>
</div>

<script>
class ZapLocalAssistantUI {
    constructor() {
        this.initializeElements();
        this.bindEvents();
    }

    initializeElements() {
        this.bubbleButton = document.getElementById('bubbleButton');
        this.chatContainer = document.getElementById('chatContainer');
        this.messagesContainer = document.getElementById('chatMessages');
        this.messageInput = document.getElementById('userMessage');
        this.sendButton = document.getElementById('sendMessage');
        this.typingIndicator = document.getElementById('typingIndicator');
    }

    bindEvents() {
        this.bubbleButton.addEventListener('click', () => this.toggleChat());
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
    }

    // [Manter os métodos sendMessage() e addMessage() existentes]
}

document.addEventListener('DOMContentLoaded', () => {
    new ZapLocalAssistantUI();
});
</script>

</body>
</html>