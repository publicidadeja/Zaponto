<?php
session_start();
include '../../includes/db.php';
include '../../includes/admin-auth.php';

// Verificar se é admin
redirecionarSeNaoAdmin();

// Estatísticas
$total_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$total_leads = $pdo->query("SELECT COUNT(*) FROM leads_enviados")->fetchColumn();
$total_mensagens = $pdo->query("SELECT COUNT(*) FROM envios_em_massa")->fetchColumn();

// Últimos usuários cadastrados
$ultimos_usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Últimas mensagens enviadas
$ultimas_mensagens = $pdo->query("SELECT * FROM envios_em_massa ORDER BY data_envio DESC LIMIT 5")->fetchAll();

// Total de mensagens por dia (últimos 7 dias)
$stats_mensagens = $pdo->query("
    SELECT DATE(data_envio) as data, COUNT(*) as total 
    FROM envios_em_massa 
    WHERE data_envio >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
    GROUP BY DATE(data_envio)
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - ZapLocal</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #3547DB;
            --primary-hover: #283593;
            --success-color: #2CC149;
            --background-color: #f7f9fc;
            --text-color: #364a63;
            --border-color: #e2e8f0;
            --card-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.05);
            --border-radius: 10px;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            font-family: 'Nunito', sans-serif;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }

        .card {
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
        }

        .stats-card {
            background: linear-gradient(45deg, var(--primary-color), var(--primary-hover));
            color: white;
        }

        .stats-card .card-body {
            position: relative;
            padding: 1.5rem;
        }

        .stats-card i {
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.5;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            font-weight: 600;
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
        }

        .card-header h5 {
            margin-bottom: 0;
            color: var(--text-color);
            font-weight: 600;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Menu Lateral -->
            <?php include 'menu.php'; ?>

            <!-- Conteúdo Principal -->
            <div class="main-content">
                <h2 class="mb-4">Dashboard</h2>
                
                <!-- Cards de Estatísticas -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <div class="card-body">
                                <h5 class="card-title">Total de Usuários</h5>
                                <h2><?php echo $total_usuarios; ?></h2>
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <div class="card-body">
                                <h5 class="card-title">Total de Leads</h5>
                                <h2><?php echo $total_leads; ?></h2>
                                <i class="fas fa-address-book fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <div class="card-body">
                                <h5 class="card-title">Total de Mensagens</h5>
                                <h2><?php echo $total_mensagens; ?></h2>
                                <i class="fas fa-envelope fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Últimos Usuários -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Últimos Usuários Cadastrados</h5>
                        <a href="usuarios.php" class="btn btn-sm btn-primary">Ver Todos</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Data de Cadastro</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultimos_usuarios as $usuario): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></td>
                                        <td>
                                            <a href="editar-usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Últimas Mensagens -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Últimas Mensagens Enviadas</h5>
                        <a href="mensagens.php" class="btn btn-sm btn-primary">Ver Todas</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Usuário</th>
                                        <th>Mensagem</th>
                                        <th>Data de Envio</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultimas_mensagens as $mensagem): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($mensagem['usuario_id']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($mensagem['mensagem'], 0, 50)) . '...'; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($mensagem['data_envio'])); ?></td>
                                        <td>
                                            <span class="badge badge-success">Enviado</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Script para toggle do menu em dispositivos móveis
        $(document).ready(function() {
            $('#sidebarCollapse').on('click', function() {
                $('.sidebar').toggleClass('active');
                $('.main-content').toggleClass('active');
            });
        });
    </script>
</body>
</html>