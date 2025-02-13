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


$(document).ready(function() {
    // Preview em tempo real
    $('input[name="titulo"], textarea[name="mensagem"]').on('input', function() {
        atualizarPreview();
    });
    
    // Exportação para Excel
    $('#exportarExcel').click(function() {
        window.location.href = 'export_notifications.php?format=excel&' + $.param(getFiltros());
    });
    
    // Filtros dinâmicos
    $('#aplicarFiltros').click(function() {
        refreshTable();
    });
    
    // Função para atualizar tabela
    function refreshTable() {
        $('#notificacoesTable').DataTable().ajax.reload();
    }
    
    // Função para preview
    function atualizarPreview() {
        const titulo = $('input[name="titulo"]').val();
        const mensagem = $('textarea[name="mensagem"]').val();
        $('.preview-box').html(`
            <h5>${titulo}</h5>
            <p>${mensagem}</p>
        `);
    }
});