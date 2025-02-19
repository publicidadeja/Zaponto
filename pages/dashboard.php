<?php
session_start();
include '../includes/auth.php';
redirecionarSeNaoLogado();
include '../includes/db.php';
include '../includes/functions.php';

// Consultar todos os dados do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$limites = verificarLimitesUsuario($pdo, $_SESSION['usuario_id']);

// Query para obter o total de leads atual
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads_enviados WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$totalLeads = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Verificação mais segura
if (!isset($usuario['perfil_completo']) || $usuario['perfil_completo'] == 0 || $usuario['perfil_completo'] == null) {
    include '../includes/modal_perfil.php';
}

// Definir o título da página
$page_title = 'Dashboard';

// CSS específico para esta página
$extra_css = '
<style>
    /* Cores ZapLocal */
    :root {
        --primary-color: #0098fc;
        --primary-hover: #283593;
        --success-color: #2CC149;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --background-color: #f7f9fc;
        --text-color: #364a63;
        --border-color: #e2e8f0;
        --card-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.05);
        --border-radius: 10px;
    }

    /* Container */
    .container {
        padding-top: 20px;
        max-width: 1400px;
    }

    /* Conteúdo Principal */
    .main-content {
        background: #fff;
        border-radius: var(--border-radius);
        padding: 2rem;
        box-shadow: var(--card-shadow);
        margin-bottom: 2rem;
    }

    /* Dashboard Header */
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding: 0 1rem;
    }

    .dashboard-header h1 {
        font-size: 2rem;
        color: var(--text-color);
        margin: 0;
    }

    .welcome-text {
        font-size: 1.25rem;
        color: var(--text-secondary);
        margin: 0.5rem 0 0 0;
    }

    /* Cards de Métricas */
    .metric-card {
        background: #fff;
        border-radius: var(--border-radius);
        padding: 2rem;
        box-shadow: var(--card-shadow);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
        margin-bottom: 1rem;
    }

    .metric-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1);
    }

    .metric-card h3 {
        color: var(--text-color);
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    .metric-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 1rem 0;
    }

    .metric-description {
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .metric-description i {
        color: var(--primary-color);
    }

    .progress {
        height: 10px;
        background-color: #e9ecef;
        border-radius: 5px;
        margin: 10px 0;
    }

    .progress-bar {
        border-radius: 5px;
        transition: width 0.3s ease;
    }

    .progress-bar.bg-warning {
        background-color: #ffc107 !important;
    }

    .progress-bar.bg-success {
        background-color: #28a745 !important;
    }


    /* Responsividade */
    @media (max-width: 991px) {
        .container {
            padding: 1rem;
        }

        .main-content {
            padding: 1.5rem;
        }

        .metric-card {
            padding: 1.5rem;
        }
        .progress {
            height: 8px;
        }
         .col-lg-4 { /* Ajuste para 3 colunas em telas médias */
            flex: 0 0 100%;
            max-width: 100%;
        }
    }

    @media (max-width: 767px) {
        .dashboard-header {
            flex-direction: column;
            align-items: flex-start;
            padding: 0;
        }

        .welcome-text {
            margin-top: 0.5rem;
        }

        .metric-value {
            font-size: 2rem;
        }
        .progress {
            height: 7px;
        }
        .col-md-6 { /* Ajuste para 1 coluna em telas pequenas */
            flex: 0 0 100%;
            max-width: 100%;
        }
    }

    @media (max-width: 575px) {
        .container {
            padding: 0.5rem;
        }

        .main-content {
            padding: 1rem;
        }

        .metric-card {
            padding: 1rem;
        }

        .dashboard-header h1 {
            font-size: 1.75rem;
        }
        .progress {
            height: 6px;
        }
    }
</style>';



$stmt = $pdo->prepare("SELECT COUNT(*) AS total_leads FROM leads_enviados WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$total_leads = $stmt->fetch()['total_leads'];

// For total envios em massa
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_envios_massa FROM fila_mensagens WHERE usuario_id = ? AND status = 'ENVIADO'");
$stmt->execute([$_SESSION['usuario_id']]);
$total_envios_massa = $stmt->fetch()['total_envios_massa'];


// For último envio
$stmt = $pdo->prepare("SELECT MAX(created_at) AS ultimo_envio FROM fila_mensagens WHERE usuario_id = ? AND status = 'ENVIADO'");
$stmt->execute([$_SESSION['usuario_id']]);
$ultimo_envio = $stmt->fetch()['ultimo_envio'];

$stmt = $pdo->prepare("SELECT nome, numero FROM leads_enviados WHERE usuario_id = ? ORDER BY data_envio DESC LIMIT 1");
$stmt->execute([$_SESSION['usuario_id']]);
$ultimo_lead = $stmt->fetch();

// Buscar limites do plano atual do usuário
$stmt = $pdo->prepare("
    SELECT a.*, p.limite_mensagens 
    FROM assinaturas a
    JOIN planos p ON a.plano_id = p.id
    WHERE a.usuario_id = ? 
    AND a.status = 'ativo'
    ORDER BY a.data_inicio DESC 
    LIMIT 1
");
$stmt->execute([$_SESSION['usuario_id']]);
$plano = $stmt->fetch(PDO::FETCH_ASSOC);

// Contar mensagens enviadas no mês atual
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM leads_enviados 
    WHERE usuario_id = ? 
    AND MONTH(data_envio) = MONTH(CURRENT_DATE())
    AND YEAR(data_envio) = YEAR(CURRENT_DATE())
");
$stmt->execute([$_SESSION['usuario_id']]);
$mensagens_enviadas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Verificar se o plano é ilimitado
$is_ilimitado = ($plano['limite_mensagens'] == -1);

// Calcular mensagens restantes
if ($is_ilimitado) {
    $mensagens_restantes = "Ilimitado";
    $percentual_usado = 0; // Não mostra barra de progresso para plano ilimitado
} else {
    $mensagens_restantes = $plano['limite_mensagens'] - $mensagens_enviadas;
    $percentual_usado = ($mensagens_enviadas / $plano['limite_mensagens']) * 100;
}


// Estatísticas de mensagens (hoje, esta semana)
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN DATE(data_envio) = CURRENT_DATE THEN 1 ELSE 0 END) as total_hoje,
        SUM(CASE WHEN WEEK(data_envio) = WEEK(CURRENT_DATE()) THEN 1 ELSE 0 END) as total_semana
    FROM leads_enviados
    WHERE usuario_id = ?
");
$stmt->execute([$_SESSION['usuario_id']]);
$stats_mensagens = $stmt->fetch(PDO::FETCH_ASSOC);


// Incluir o header padronizado
include '../includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <div class="dashboard-header">
            <div>
                <h1>Dashboard</h1>
                <p class="welcome-text">Bem-vindo(a), <?php echo htmlspecialchars($usuario['nome']); ?></p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Primeira Linha -->
            <div class="col-12 col-md-6 col-lg-4">
    <div class="metric-card">
        <h3>Total de Leads</h3>
        <div class="metric-value"><?php echo number_format($total_leads); ?></div>
        <?php if ($limites): ?>
            <?php if ($limites['limite_leads'] == -1): ?>
                <p class="metric-description">
                    <i class="fas fa-users"></i> Leads cadastrados (Ilimitado)
                </p>
            <?php else: ?>
                <p class="metric-description">
                    <i class="fas fa-users"></i> Leads cadastrados (<?php echo number_format($total_leads); ?> de <?php echo number_format($limites['limite_leads']); ?>)
                </p>
                <div class="progress">
                    <div class="progress-bar <?php echo ($total_leads / $limites['limite_leads'] > 0.8) ? 'bg-warning' : 'bg-success'; ?>"
                         role="progressbar"
                         style="width: <?php echo min(($total_leads / $limites['limite_leads'] * 100), 100); ?>%">
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="metric-description">
                <i class="fas fa-users"></i> Leads cadastrados
            </p>
        <?php endif; ?>
    </div>
</div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="metric-card">
                    <h3>Envios em Massa</h3>
                    <div class="metric-value">
                        <?php echo number_format($total_envios_massa); ?>
                    </div>
                    <p class="metric-description">
                        <i class="fas fa-paper-plane"></i> Campanhas realizadas
                    </p>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="metric-card">
                    <h3>Último Envio</h3>
                    <div class="metric-value">
                        <?php
                        if ($ultimo_envio) {
                            echo date('d/m/Y H:i', strtotime($ultimo_envio));
                        } else {
                            echo '-';
                        }
                        ?>
                    </div>
                    <p class="metric-description">
                        <i class="fas fa-clock"></i> Data do último envio
                    </p>
                </div>
            </div>

            <!-- Segunda Linha -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="metric-card">
                    <h3>Lead Recente</h3>
                    <div class="metric-value">
                        <?php echo $ultimo_lead ? htmlspecialchars($ultimo_lead['nome']) : '-'; ?>
                    </div>
                    <p class="metric-description"><i class="fas fa-user"></i> Último lead cadastrado</p>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
    <div class="metric-card">
        <h3>Limite de Mensagens</h3>
        <div class="metric-value">
            <?php 
            // Verificar se o plano é ilimitado
            $is_ilimitado = ($plano['limite_mensagens'] == -1);
            echo $is_ilimitado ? "Ilimitado" : number_format($mensagens_restantes); 
            ?>
        </div>
        <p class="metric-description">
            <i class="fas fa-envelope"></i> Mensagens restantes
        </p>
        <?php if (!$is_ilimitado): ?>
            <div class="progress">
                <div class="progress-bar <?php echo $percentual_usado > 80 ? 'bg-warning' : 'bg-success'; ?>"
                     role="progressbar"
                     style="width: <?php echo min($percentual_usado, 100); ?>%"
                     aria-valuenow="<?php echo min($percentual_usado, 100); ?>"
                     aria-valuemin="0"
                     aria-valuemax="100">
                </div>
            </div>
            <p class="metric-description">
                <small>
                    <?php echo number_format($mensagens_enviadas); ?> de <?php echo number_format($plano['limite_mensagens']); ?> utilizadas
                </small>
            </p>
        <?php endif; ?>
    </div>
</div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="metric-card">
                    <h3>Histórico de Envios</h3>
                    <div class="metric-value">
                        <?php echo $stats_mensagens['total_hoje'] ?? 0; ?>
                    </div>
                     <p class="metric-description">
                        <i class="fas fa-calendar-day"></i> Envios hoje
                    </p>
                    <div class="metric-value" style="margin-top: 0.5rem;">
                        <?php echo $stats_mensagens['total_semana'] ?? 0; ?>
                    </div>
                    <p class="metric-description">
                        <i class="fas fa-calendar-week"></i> Envios esta semana
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!$usuario['perfil_completo']): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var perfilModal = new bootstrap.Modal(document.getElementById('perfilModal'));
    perfilModal.show();

    document.getElementById('formPerfil').addEventListener('submit', function(e) {
        e.preventDefault();

        fetch('../ajax/salvar_perfil.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                perfilModal.hide();
                window.location.reload(); // Força o recarregamento da página
            } else {
                alert('Erro ao salvar os dados. Por favor, tente novamente.');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao salvar os dados. Por favor, tente novamente.');
        });
    });
});


function debug_query($pdo) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total, MAX(data_envio) as ultimo_envio 
        FROM fila_mensagens 
        WHERE usuario_id = ? 
        AND status = 'ENVIADO'
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $debug = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Debug Dashboard - Total Envios: " . $debug['total'] . " - Último Envio: " . $debug['ultimo_envio']);
}

// Chame a função
debug_query($pdo);
</script>
<?php endif; ?>


</body>
</html>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/notifications.js"></script>

<?php include '../includes/footer.php'; ?>