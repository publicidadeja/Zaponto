<?php

/**
 *  ZapLocal - header.php
 *
 *  Este arquivo é o cabeçalho comum para todas as páginas do sistema ZapLocal.
 *
 *  Contém:
 *  - Inicialização de sessão.
 *  - Inclusão de arquivos de funções, banco de dados e notificações.
 *  - Verificação de autenticação e status do usuário (teste/assinatura).
 *  - Definição dinâmica da URL base.
 *  - Layout HTML do cabeçalho (estilos CSS, scripts JS, barra de navegação, alertas, modais).
 */

//---------------------------------------------------------------------
// Configurações Iniciais e Constantes
//---------------------------------------------------------------------

define('PUBLIC_PAGES', ['login.php', 'registro.php', 'recuperar-senha.php']); // Páginas sem autenticação
define('PROJECT_PATH', '/zaplocal1.0');  // Subdiretório do projeto (ajustar se necessário)


//---------------------------------------------------------------------
// Inicialização da Sessão
//---------------------------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//---------------------------------------------------------------------
// Inclusão de Arquivos
//---------------------------------------------------------------------

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/notifications.php';


//---------------------------------------------------------------------
// Funções Auxiliares
//---------------------------------------------------------------------

/**
 *  Determina a URL base do projeto (considera HTTPS e subdiretórios).
 *
 *  @return string URL base completa.
 */
function getBaseUrl(): string
{
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $protocol = $isHttps ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . $host;

    // Adiciona subdiretório em localhost/127.0.0.1
    if (in_array($host, ['localhost', '127.0.0.1'])) {
        $baseUrl .= PROJECT_PATH;
    }
    return $baseUrl;
}


/**
 *  Verifica a autenticação do usuário. Redireciona para login se não autenticado
 *  e se a página atual não for pública.
 *
 *  @param string $currentPage Página atual.
 *  @param string $baseUrl     URL base.
 */
function checkAuthentication(string $currentPage, string $baseUrl): void
{
    if (!in_array($currentPage, PUBLIC_PAGES) && !isset($_SESSION['usuario_id'])) {
        header('Location: ' . $baseUrl . '/login.php');
        exit;
    }
}

/**
 *  Verifica o status do usuário (teste/assinatura) e redireciona para a página
 *  de planos, se necessário.
 *
 *  @param PDO    $pdo          Conexão com o banco de dados.
 *  @param string $currentPage  Página atual.
 *  @param string $baseUrl      URL base.
 */
function checkUserStatus(PDO $pdo, string $currentPage, string $baseUrl): void
{
    if (isset($_SESSION['usuario_id']) && !in_array($currentPage, PUBLIC_PAGES)) {
        $isTrial = verificarPeriodoTeste($pdo, $_SESSION['usuario_id']);
        $hasSubscription = verificarAssinaturaAtiva($pdo, $_SESSION['usuario_id']);

        if (!$isTrial && !$hasSubscription && $currentPage !== 'planos.php') {
            header('Location: ' . $baseUrl . '/pages/planos.php');
            exit;
        }
    }
}


//---------------------------------------------------------------------
//  Inicialização de Variáveis e Verificações
//---------------------------------------------------------------------

$baseUrl = getBaseUrl();
$currentPage = basename($_SERVER['PHP_SELF']);

checkAuthentication($currentPage, $baseUrl);

// Verifica status apenas se a conexão com o BD existir
if (isset($pdo)) {
    checkUserStatus($pdo, $currentPage, $baseUrl);
}

// Dados do usuário (carrega apenas se necessário)
$isTrial = $hasSubscription = null;
if (isset($_SESSION['usuario_id'], $pdo) && !in_array($currentPage, PUBLIC_PAGES)) {
    $isTrial = verificarPeriodoTeste($pdo, $_SESSION['usuario_id']);
    $hasSubscription = verificarAssinaturaAtiva($pdo, $_SESSION['usuario_id']);
}

//---------------------------------------------------------------------
//  HTML do Cabeçalho
//---------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ZapLocal' : 'ZapLocal' ?></title>
    <link rel="icon" href="<?= htmlspecialchars($baseUrl) ?>/assets/images/favicon.ico" type="image/x-icon">

    <!-- CSS (Bootstrap, Font Awesome, Google Fonts) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

    <!-- JavaScript (jQuery, Bootstrap) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- CSS Personalizado -->
    <style>
        :root {
            --primary-color: #3547DB;
            --primary-hover: #283593;
            --success-color: #2CC149;
            /* ... outras cores ... */
            --background-color: #f7f9fc;
            --text-color: #364a63;
            --border-color: #e2e8f0;
            --card-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.05);
            --border-radius: 10px;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }

        /* ... outros estilos ... */

        .navbar {
            background-color: #fff;
            box-shadow: var(--card-shadow);
            padding: 1rem 1.5rem;
        }

        .navbar-brand img {
            height: 40px;
        }

        .navbar-nav .nav-link {
            color: var(--text-color);
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            transition: color 0.2s ease, background-color 0.2s ease;
            border-radius: var(--border-radius);
        }

        .navbar-nav .nav-link i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        .navbar-nav .nav-link:hover {
            color: var(--primary-color);
            background-color: rgba(53, 71, 219, 0.1);
        }

        /* ... outros estilos ... */
        .navbar-icons {
            display: flex;
            align-items: center;
        }

        .navbar-icons a {
            color: var(--text-color);
            margin-left: 1rem;
            font-size: 1.2rem;
            transition: color 0.2s ease;
            padding: 0.5rem;
            border-radius: 50%;
        }

        .navbar-icons a:hover {
            color: var(--primary-color);
            background-color: rgba(53, 71, 219, 0.1);
        }

        @media (max-width: 991.98px) {
            .navbar-nav {
                padding: 1rem 0;
            }
            /* ... outros estilos responsivos ... */
             .navbar-icons {
                margin-top: 1rem;
                justify-content: center;
            }
        }

        .notification-dropdown {
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-item {
            display: flex;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .notification-item.read {
            opacity: 0.6;
            background-color: #f8f9fa;
        }

        .notification-item:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 50%;
            margin-right: 10px;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .notification-text {
            font-size: 0.9em;
            color: #666;
        }

        .notification-time {
            font-size: 0.8em;
            color: #999;
            margin-top: 5px;
        }
        .notification-loading {
            color: #666;
        }

        .notification-loading.show {
            display: block !important;
        }
    </style>

    <?php if (isset($extra_css)) { echo $extra_css; } ?>
</head>
<body>

    <!-- Alertas e Modais -->
    <?php if (isset($_SESSION['usuario_id'])): ?>
        <?php if ($isTrial): ?>
            <div class="alert alert-info alert-dismissible fade show text-center" role="alert">
                <strong>Período de teste:</strong> <?= htmlspecialchars($isTrial['dias_restantes']) ?> dias restantes
                <a href="<?= htmlspecialchars($baseUrl) ?>/pages/planos.php" class="btn btn-primary btn-sm ms-3">Escolher um plano</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (!$hasSubscription && $currentPage !== 'planos.php'): ?>
            <div class="modal fade" id="escolherPlanoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Escolha um Plano</h5>
                        </div>
                        <div class="modal-body">
                            <p>Seu período de teste expirou.  Para continuar, escolha um plano.</p>
                        </div>
                        <div class="modal-footer">
                            <a href="<?= htmlspecialchars($baseUrl) ?>/pages/planos.php" class="btn btn-primary">Ver Planos</a>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                $(document).ready(function() {
                    new bootstrap.Modal(document.getElementById('escolherPlanoModal')).show();
                });
            </script>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Barra de Navegação -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="<?= htmlspecialchars($baseUrl) ?>/pages/dashboard.php">
                <img src="https://publicidadeja.com.br/wp-content/uploads/2025/02/Logo-ZapLocal-fundo-escuro-1-1.png" alt="ZapLocal Logo">
            </a>

            <?php if (isset($_SESSION['usuario_id'])): ?>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= htmlspecialchars($baseUrl) ?>/pages/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= htmlspecialchars($baseUrl) ?>/pages/enviar-mensagem.php">
                                <i class="fas fa-envelope"></i> Enviar Mensagem
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= htmlspecialchars($baseUrl) ?>/pages/lista-leads.php">
                                <i class="fas fa-address-book"></i> Listar Leads
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= htmlspecialchars($baseUrl) ?>/pages/dispositivos.php">
                                <i class="fas fa-mobile-alt"></i> Dispositivos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= htmlspecialchars($baseUrl) ?>/pages/envio-massa.php">
                                <i class="fas fa-rocket"></i> Envio em Massa
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= htmlspecialchars($baseUrl) ?>/pages/configuracoes.php">
                                <i class="fas fa-cog"></i> Configurações
                            </a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="navbar-icons">

                            <?php
                            // Inclui o script de notificações e busca as notificações do usuário
                            include_once 'notifications.php';
                            $notifications = buscarNotificacoes($pdo, $_SESSION['usuario_id']);
                            $notificationCount = count($notifications);
                            ?>

                            <div class="dropdown">
                                <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-bell"></i>
                                    <?php if ($notificationCount > 0): ?>
                                        <span class="badge bg-danger"><?= $notificationCount ?></span>
                                    <?php endif; ?>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                                    <div class="dropdown-header">Notificações (<span id="notification-count"><?= $notificationCount ?></span>)</div>
                                    <div class="notification-list">
                                        <div class="notification-loading text-center p-3 d-none">
                                            <i class="fas fa-spinner fa-spin"></i> Carregando...
                                        </div>
                                        <?php if ($notificationCount > 0): ?>
                                            <?php foreach ($notifications as $notification): ?>
                                                <a href="#" class="notification-item <?= $notification['lida'] ? 'read' : '' ?>"
                                                   data-id="<?= $notification['id'] ?>">
                                                    <div class="notification-icon">
                                                        <?php
                                                        // Define o ícone com base no tipo de notificação
                                                        $icon = match ($notification['tipo']) {
                                                            'plano' => 'fa-calendar',
                                                            'envios' => 'fa-paper-plane',
                                                            'leads' => 'fa-users',
                                                            'admin' => 'fa-bell',
                                                            default => 'fa-bell',
                                                        };
                                                        ?>
                                                        <i class="fas <?= $icon ?>"></i>
                                                    </div>
                                                    <div class="notification-content">
                                                        <div class="notification-title"><?= htmlspecialchars($notification['titulo']) ?></div>
                                                        <div class="notification-text"><?= htmlspecialchars($notification['mensagem']) ?></div>
                                                        <div class="notification-time">
                                                            <?= date('d/m/Y H:i', strtotime($notification['data_criacao'])) ?>
                                                        </div>
                                                    </div>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="dropdown-item">Nenhuma notificação</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <a href="<?= htmlspecialchars($baseUrl) ?>/pages/perfil.php" title="Perfil"><i class="fas fa-user-circle"></i></a>
                            <a href="<?= htmlspecialchars($baseUrl) ?>/logout.php" title="Sair"><i class="fas fa-sign-out-alt"></i></a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </nav>
    <script src="<?= $baseUrl ?>/js/notifications.js"></script>


    <script>
    // Script para marcar notificações como lidas via AJAX
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
                    body: JSON.stringify({ notificacao_id: notificationId })
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
    </script>

<?php include 'assistant-chat.php'; ?>
<script src="<?php echo $baseUrl; ?>assets/js/ai-assistant.js"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</body>
</body>
</html>