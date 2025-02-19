<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/GeminiChat.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Sua chave API do Gemini
$apiKey = 'SUA_CHAVE_API_AQUI';

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
        .chat-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .chat-messages {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
        }
        .user-message {
            background-color: #007bff;
            color: white;
            margin-left: 20%;
        }
        .assistant-message {
            background-color: #f8f9fa;
            margin-right: 20%;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-messages" id="chatMessages">
            <!-- Mensagens aparecerão aqui -->
        </div>
        <form id="chatForm" class="d-flex">
            <input type="text" id="messageInput" class="form-control me-2" placeholder="Digite sua mensagem...">
            <button type="submit" class="btn btn-primary">Enviar</button>
        </form>
    </div>

    <script>
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const chatMessages = document.getElementById('chatMessages');

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            // Adiciona mensagem do usuário
            addMessage(message, 'user');
            messageInput.value = '';

            try {
                const response = await fetch('chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `message=${encodeURIComponent(message)}`
                });

                const data = await response.json();
                if (data.success) {
                    addMessage(data.message, 'assistant');
                } else {
                    addMessage('Erro: ' + data.error, 'assistant');
                }
            } catch (error) {
                addMessage('Erro ao enviar mensagem', 'assistant');
            }
        });

        function addMessage(message, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}-message`;
            messageDiv.textContent = message;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>