document.addEventListener('DOMContentLoaded', function() {
    const assistant = {
        init: function() {
            this.widget = document.getElementById('ia-assistant');
            this.messages = this.widget.querySelector('.ia-messages');
            this.input = this.widget.querySelector('textarea');
            this.sendBtn = this.widget.querySelector('.ia-send');
            
            this.bindEvents();
        },
        
        bindEvents: function() {
            this.sendBtn.addEventListener('click', () => this.sendMessage());
            this.input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        },
        
        sendMessage: async function() {
            const message = this.input.value.trim();
            if (!message) return;
            
            this.addMessage('user', message);
            this.input.value = '';
            
            try {
                const response = await fetch('/pages/gerar_sugestao_ia.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({prompt: message})
                });
                
                const data = await response.json();
                if (data.error) {
                    this.addMessage('error', data.error);
                } else {
                    this.addMessage('assistant', data.response.content);
                }
            } catch (error) {
                this.addMessage('error', 'Erro ao processar sua mensagem');
            }
        },
        
        addMessage: function(type, content) {
            const div = document.createElement('div');
            div.className = `message ${type}`;
            div.textContent = content;
            this.messages.appendChild(div);
            this.messages.scrollTop = this.messages.scrollHeight;
        }
    };
    
    assistant.init();
});