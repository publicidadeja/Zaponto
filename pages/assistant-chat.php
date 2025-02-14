<div id="ai-assistant-widget" class="ai-assistant-closed">
    <div class="ai-assistant-header">
        <span>Assistente IA</span>
        <button id="ai-assistant-toggle">×</button>
    </div>
    <div class="ai-assistant-body">
        <div id="ai-assistant-messages"></div>
        <div class="ai-assistant-input">
            <textarea id="ai-assistant-prompt" placeholder="Digite sua mensagem..."></textarea>
            <button id="ai-assistant-send">Enviar</button>
        </div>
    </div>
</div>

<style>
#ai-assistant-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 350px;
    height: 500px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    z-index: 1000;
}

.ai-assistant-closed {
    height: 50px !important;
}

.ai-assistant-header {
    padding: 10px;
    background: #2CC149;
    color: white;
    border-radius: 10px 10px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ai-assistant-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 10px;
    overflow: hidden;
}

#ai-assistant-messages {
    flex: 1;
    overflow-y: auto;
    margin-bottom: 10px;
}

.ai-assistant-input {
    display: flex;
    gap: 10px;
}

#ai-assistant-prompt {
    flex: 1;
    resize: none;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 5px;
    height: 40px;
}

#ai-assistant-send {
    padding: 8px 15px;
    background: #2CC149;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.message {
    margin-bottom: 10px;
    padding: 8px;
    border-radius: 5px;
}

.user-message {
    background: #e9ecef;
    margin-left: 20px;
}

.assistant-message {
    background: #f8f9fa;
    margin-right: 20px;
}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    const widget = document.getElementById('ai-assistant-widget');
    const toggleBtn = document.getElementById('ai-assistant-toggle');
    const messagesContainer = document.getElementById('ai-assistant-messages');
    const promptInput = document.getElementById('ai-assistant-prompt');
    const sendBtn = document.getElementById('ai-assistant-send');

    // Toggle chat
    toggleBtn.addEventListener('click', () => {
        widget.classList.toggle('ai-assistant-closed');
        toggleBtn.textContent = widget.classList.contains('ai-assistant-closed') ? '□' : '×';
    });

    // Send message
    async function sendMessage() {
        const prompt = promptInput.value.trim();
        if (!prompt) return;

        // Add user message to chat
        addMessage('user', prompt);
        promptInput.value = '';

        try {
            const response = await fetch('/pages/claude_proxy.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ prompt: prompt })
            });

            const data = await response.json();
            
            if (data.success) {
                addMessage('assistant', data.content);
            } else {
                addMessage('assistant', 'Desculpe, ocorreu um erro ao processar sua mensagem.');
            }
        } catch (error) {
            console.error('Erro:', error);
            addMessage('assistant', 'Desculpe, ocorreu um erro ao processar sua mensagem.');
        }
    }

    function addMessage(type, content) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', `${type}-message`);
        messageDiv.textContent = content;
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    sendBtn.addEventListener('click', sendMessage);
    promptInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
});
</script>