<?php
session_start();
include '../includes/auth.php';
redirecionarSeNaoLogado();
include '../includes/db.php';

// Consultar todos os dados do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);



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
    }

    @media (max-width: 768px) {
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
    }

    @media (max-width: 576px) {
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
    }
</style>';



$stmt = $pdo->prepare("SELECT COUNT(*) AS total_leads FROM leads_enviados WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$total_leads = $stmt->fetch()['total_leads'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS total_envios_massa FROM envios_em_massa WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$total_envios_massa = $stmt->fetch()['total_envios_massa'];

$stmt = $pdo->prepare("SELECT MAX(data_envio) AS ultimo_envio FROM envios_em_massa WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$ultimo_envio = $stmt->fetch()['ultimo_envio'];

$stmt = $pdo->prepare("SELECT nome, numero FROM leads_enviados WHERE usuario_id = ? ORDER BY data_envio DESC LIMIT 1");
$stmt->execute([$_SESSION['usuario_id']]);
$ultimo_lead = $stmt->fetch();

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
            <div class="col-12 col-md-6 col-lg-3">
                <div class="metric-card">
                    <h3>Total de Leads</h3>
                    <div class="metric-value"><?php echo number_format($total_leads); ?></div>
                    <p class="metric-description"><i class="fas fa-users"></i> Leads cadastrados</p>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="metric-card">
                    <h3>Envios em Massa</h3>
                    <div class="metric-value"><?php echo number_format($total_envios_massa); ?></div>
                    <p class="metric-description"><i class="fas fa-paper-plane"></i> Campanhas realizadas</p>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="metric-card">
                    <h3>Último Envio</h3>
                    <div class="metric-value">
                        <?php echo $ultimo_envio ? date('d/m/Y H:i', strtotime($ultimo_envio)) : '-'; ?>
                    </div>
                    <p class="metric-description"><i class="fas fa-clock"></i> Data do último envio</p>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="metric-card">
                    <h3>Lead Recente</h3>
                    <div class="metric-value">
                        <?php echo $ultimo_lead ? htmlspecialchars($ultimo_lead['nome']) : '-'; ?>
                    </div>
                    <p class="metric-description"><i class="fas fa-user"></i> Último lead cadastrado</p>
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
</script>
<?php endif; ?>


</body>
</html>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/notifications.js"></script>

<?php include '../includes/footer.php'; ?>