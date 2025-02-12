<?php

/**
 * Header.php - Arquivo de cabeçalho comum para todas as páginas do sistema ZapLocal.
 *
 * Este arquivo contém:
 * - Inicialização de sessão.
 * - Inclusão de arquivos de funções e banco de dados.
 * - Verificação de autenticação do usuário.
 * - Definição dinâmica da URL base.
 * - Verificação de período de teste e assinatura.
 * - Definição do layout HTML do cabeçalho, incluindo estilos CSS e scripts JS.
 * - Barra de navegação com links e ícones.
 * - Alertas e modais relacionados ao status do usuário.
 */

// Configurações Iniciais
define('PUBLIC_PAGES', ['login.php', 'registro.php', 'recuperar-senha.php']);  // Páginas que não exigem login
define('PROJECT_PATH', '/zaplocal1.0'); // Subdiretório do projeto, se houver.  Mude se o nome da pasta for diferente.

// Funções de Inicialização
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';


/**
 * Determina a URL base do projeto, considerando HTTPS e subdiretórios.
 *
 * @return string A URL base completa do projeto.
 */
function getBaseUrl(): string
{
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    $protocol = $isHttps ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . $host;

    // Adiciona o subdiretório do projeto se estiver em localhost ou 127.0.0.1
    if ($host === 'localhost' || $host === '127.0.0.1') {
        $baseUrl .= PROJECT_PATH;
    }
    return $baseUrl;
}

$base_url = getBaseUrl();
$current_page = basename($_SERVER['PHP_SELF']);


/**
 * Verifica se o usuário está autenticado.  Redireciona para a página de login
 * se não estiver autenticado e a página atual não for uma página pública.
 *
 * @param string $currentPage A página atual sendo acessada.
 * @param string $baseUrl     A URL base do projeto.
 * @return void
 */
function checkAuthentication(string $currentPage, string $baseUrl): void
{
    if (!in_array($currentPage, PUBLIC_PAGES) && !isset($_SESSION['usuario_id'])) {
        header('Location: ' . $baseUrl . '/login.php');
        exit;
    }
}

checkAuthentication($current_page, $base_url);


/**
 * Verifica o status do usuário (período de teste, assinatura) e redireciona
 * para a página de planos se necessário.
 *
 * @param PDO    $pdo          Conexão com o banco de dados.
 * @param string $currentPage  A página atual.
 * @param string $baseUrl      A URL base.
 * @return void
 */
function checkUserStatus(PDO $pdo, string $currentPage, string $baseUrl): void
{
    if (isset($_SESSION['usuario_id']) && !in_array($currentPage, PUBLIC_PAGES)) {
        $periodo_teste = verificarPeriodoTeste($pdo, $_SESSION['usuario_id']);
        $tem_assinatura = verificarAssinaturaAtiva($pdo, $_SESSION['usuario_id']);

        if (!$periodo_teste && !$tem_assinatura && $currentPage !== 'planos.php') {
            header('Location: ' . $baseUrl . '/pages/planos.php');
            exit;
        }
    }
}

// Só verifica o status se a conexão com o banco de dados existir
if (isset($pdo)) {
    checkUserStatus($pdo, $current_page, $base_url);
}

// Dados do Usuário (se logado) - Carrega apenas se necessário
$periodo_teste = $tem_assinatura = null;
if (isset($_SESSION['usuario_id'], $pdo) && !in_array($current_page, PUBLIC_PAGES)) {
    $periodo_teste = verificarPeriodoTeste($pdo, $_SESSION['usuario_id']);
    $tem_assinatura = verificarAssinaturaAtiva($pdo, $_SESSION['usuario_id']);
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ZapLocal' : 'ZapLocal'; ?></title>

    <link rel="icon" href="<?php echo htmlspecialchars($base_url); ?>/assets/images/favicon.ico" type="image/x-icon">

    <!-- CSS Padrão (Bootstrap, Font Awesome, Google Fonts) -->
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
            /* ... outras variáveis de cor ... */
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

    </style>

    <?php if (isset($extra_css)) { echo $extra_css; } ?>
</head>
<body>

    <!-- Alertas e Modais -->
    <?php if (isset($_SESSION['usuario_id'])): ?>
        <?php if ($periodo_teste): ?>
            <div class="alert alert-info alert-dismissible fade show text-center" role="alert">
                <strong>Período de teste:</strong> <?php echo htmlspecialchars($periodo_teste['dias_restantes']); ?> dias restantes
                <a href="<?php echo htmlspecialchars($base_url); ?>/pages/planos.php" class="btn btn-primary btn-sm ms-3">Escolher um plano</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (!$tem_assinatura && $current_page !== 'planos.php'): ?>
            <div class="modal fade" id="escolherPlanoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Escolha um Plano</h5>
                        </div>
                        <div class="modal-body">
                            <p>Seu período de teste expirou. Para continuar usando a plataforma, escolha um de nossos planos.</p>
                        </div>
                        <div class="modal-footer">
                            <a href="<?php echo htmlspecialchars($base_url); ?>/pages/planos.php" class="btn btn-primary">Ver Planos Disponíveis</a>
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
            <a class="navbar-brand" href="<?php echo htmlspecialchars($base_url); ?>/pages/dashboard.php">
                <img src="https://publicidadeja.com.br/wp-content/uploads/2025/02/Logo-ZapLocal-fundo-escuro-1-1.png" alt="ZapLocal Logo">
            </a>

            <?php if (isset($_SESSION['usuario_id'])): ?>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo htmlspecialchars($base_url); ?>/pages/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo htmlspecialchars($base_url); ?>/pages/enviar-mensagem.php">
                                <i class="fas fa-envelope"></i> Enviar Mensagem
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo htmlspecialchars($base_url); ?>/pages/lista-leads.php">
                                <i class="fas fa-address-book"></i> Listar Leads
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo htmlspecialchars($base_url); ?>/pages/dispositivos.php">
                                <i class="fas fa-mobile-alt"></i> Dispositivos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo htmlspecialchars($base_url); ?>/pages/envio-massa.php">
                                <i class="fas fa-rocket"></i> Envio em Massa
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo htmlspecialchars($base_url); ?>/pages/configuracoes.php">
                                <i class="fas fa-cog"></i> Configurações
                            </a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="navbar-icons">
                            <a href="#" title="Notificações"><i class="fas fa-bell"></i></a>
                            <a href="<?php echo htmlspecialchars($base_url); ?>/pages/perfil.php" title="Perfil"><i class="fas fa-user-circle"></i></a>
                            <a href="<?php echo htmlspecialchars($base_url); ?>/logout.php" title="Sair"><i class="fas fa-sign-out-alt"></i></a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </nav>