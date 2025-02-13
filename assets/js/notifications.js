// /js/notifications.js
document.addEventListener('DOMContentLoaded', function() {
    const notificationItems = document.querySelectorAll('.notification-item');
    
    notificationItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const notificationId = this.dataset.id;
            
            fetch('../ajax/marcar_notificacao_lida.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    notificacao_id: notificationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.add('read');
                    // Atualiza o visual da notificação
                    this.style.opacity = '0.6';
                    this.style.backgroundColor = '#f8f9fa';
                    
                    // Atualiza o contador de notificações
                    const badge = document.querySelector('.nav-link .badge');
                    if (badge) {
                        const count = parseInt(badge.textContent) - 1;
                        if (count > 0) {
                            badge.textContent = count;
                        } else {
                            badge.remove();
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Erro ao marcar notificação como lida:', error);
            });
        });
    });
});