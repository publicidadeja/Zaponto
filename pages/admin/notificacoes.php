<?php
session_start();
include '../../includes/db.php';
include '../../includes/admin-auth.php';

require_once '../../vendor/autoload.php';


// Verificar se é admin
redirecionarSeNaoAdmin();

// Processar o envio de nova notificação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $titulo = trim($_POST['titulo']);
        $mensagem = trim($_POST['mensagem']);
        $tipo = $_POST['tipo'];
        
        // Buscar todos os usuários ativos
        $stmt = $pdo->query("SELECT id FROM usuarios WHERE status = 'ativo'");
        $usuarios = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($usuarios)) {
            throw new Exception("Nenhum usuário ativo encontrado.");
        }
        
        // Iniciar transação
        $pdo->beginTransaction();
        
        // Preparar query para inserção
        $stmt = $pdo->prepare("
            INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, data_criacao) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        // Inserir notificação para cada usuário
        foreach ($usuarios as $usuario_id) {
            $stmt->execute([$usuario_id, $tipo, $titulo, $mensagem]);
        }
        
        // Confirmar transação
        $pdo->commit();
        
        $_SESSION['mensagem'] = "Notificação enviada com sucesso para " . count($usuarios) . " usuários!";
        header('Location: notificacoes.php');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['erro'] = "Erro ao enviar notificação: " . $e->getMessage();
        header('Location: notificacoes.php');
        exit;
    }
}

// Buscar histórico de notificações
try {
    $query = "
    SELECT 
        n.id,
        n.tipo,
        n.titulo,
        n.mensagem,
        n.data_criacao,
        COUNT(DISTINCT n.usuario_id) as total_usuarios,
        COUNT(CASE WHEN n.lida = 1 THEN 1 END) as total_lidas,
        ROUND((COUNT(CASE WHEN n.lida = 1 THEN 1 END) * 100.0 / COUNT(*)), 2) as taxa_leitura,
        MAX(n.data_leitura) as ultima_leitura
    FROM notificacoes n
    WHERE 1=1
    " . ($filtroTipo ? " AND n.tipo = :tipo" : "") . "
    " . ($dataInicio ? " AND n.data_criacao >= :data_inicio" : "") . "
    " . ($dataFim ? " AND n.data_criacao <= :data_fim" : "") . "
    GROUP BY n.id, n.tipo, n.titulo, n.mensagem, n.data_criacao
    ORDER BY n.data_criacao DESC
";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['erro'] = "Erro ao carregar notificações: " . $e->getMessage();
    $notificacoes = [];
}


?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Notificações - Admin</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .main-content {
            padding: 2rem;
            margin-left: 250px;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .notification-type {
            padding: 0.5em 1em;
            border-radius: 30px;
            font-size: 0.875em;
        }
        
        .type-sistema { background-color: #3547DB; color: white; }
        .type-plano { background-color: #2CC149; color: white; }
        .type-aviso { background-color: #FFC107; color: black; }
        .type-atualizacao { background-color: #17A2B8; color: white; }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }

        /* /assets/style.css */
.filtros-container {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.filtros-container .form-group {
    margin-bottom: 15px;
}

.export-buttons {
    margin-bottom: 20px;
}

.export-buttons .btn {
    margin-right: 10px;
}

.stats-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.stats-card .title {
    color: #666;
    font-size: 14px;
    margin-bottom: 5px;
}

.stats-card .value {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.notification-preview {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    margin-top: 10px;
}

.segmentation-options {
    margin-top: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
}
    </style>
</head>
<body>
<?php include 'menu.php'; ?>

    
    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Gerenciar Notificações</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaNotificacao">
                    <i class="fas fa-plus-circle me-2"></i>Nova Notificação
                </button>
            </div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5>Total de Notificações</h5>
                <h2><?php echo $totalNotificacoes; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5>Taxa Média de Leitura</h5>
                <h2><?php echo number_format($taxaMediaLeitura, 1); ?>%</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5>Notificações Hoje</h5>
                <h2><?php echo $notificacoesHoje; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h5>Usuários Ativos</h5>
                <h2><?php echo $usuariosAtivos; ?></h2>
            </div>
        </div>
    </div>
</div>
            
            <!-- Alerts -->
            <?php if (isset($_SESSION['mensagem'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php 
                    echo $_SESSION['mensagem'];
                    unset($_SESSION['mensagem']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['erro'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php 
                    echo $_SESSION['erro'];
                    unset($_SESSION['erro']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form class="row g-3">
            <div class="col-md-3">
                <label>Filtrar por Tipo</label>
                <select class="form-select" id="filtroTipo">
                    <option value="">Todos</option>
                    <option value="sistema">Sistema</option>
                    <option value="plano">Plano</option>
                    <option value="aviso">Aviso</option>
                    <option value="atualizacao">Atualização</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>Data Início</label>
                <input type="date" class="form-control" id="dataInicio">
            </div>
            <div class="col-md-3">
                <label>Data Fim</label>
                <input type="date" class="form-control" id="dataFim">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-primary" id="aplicarFiltros">
                    <i class="fas fa-filter me-2"></i>Aplicar Filtros
                </button>
            </div>
        </form>
    </div>
</div>


<div class="btn-group mb-3">
    <button class="btn btn-success" id="exportarExcel">
        <i class="fas fa-file-excel me-2"></i>Exportar Excel
    </button>
    <button class="btn btn-danger" id="exportarPDF">
        <i class="fas fa-file-pdf me-2"></i>Exportar PDF
    </button>
</div>

<div class="modal-body">
    <div class="mb-3">
        <label class="form-label">Agendar Envio</label>
        <input type="datetime-local" name="data_agendamento" class="form-control">
    </div>
    
    <div class="mb-3">
        <label class="form-label">Segmentação de Usuários</label>
        <select name="segmentacao" class="form-select">
            <option value="todos">Todos os Usuários</option>
            <option value="plano_ativo">Apenas Planos Ativos</option>
            <option value="plano_vencendo">Planos Próximos ao Vencimento</option>
        </select>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Preview da Notificação</label>
        <div class="preview-box p-3 border rounded"></div>
    </div>
</div>
            
            <!-- Notifications Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="notificacoesTable">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Título</th>
                                    <th>Mensagem</th>
                                    <th>Usuários</th>
                                    <th>Lidas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notificacoes as $notif): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($notif['data_criacao'])); ?></td>
                                        <td>
                                            <span class="notification-type type-<?php echo $notif['tipo']; ?>">
                                                <?php echo ucfirst($notif['tipo']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($notif['titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($notif['mensagem']); ?></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?php echo $notif['total_usuarios']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                <?php echo $notif['total_lidas']; ?>
                                            </span>
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

    
    <!-- Modal Nova Notificação -->
    <div class="modal fade" id="modalNovaNotificacao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Notificação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formNotificacao">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tipo da Notificação</label>
                            <select name="tipo" class="form-select" required>
                                <option value="sistema">Sistema</option>
                                <option value="plano">Plano</option>
                                <option value="aviso">Aviso</option>
                                <option value="atualizacao">Atualização</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Título</label>
                            <input type="text" name="titulo" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mensagem</label>
                            <textarea name="mensagem" class="form-control" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Notificação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#notificacoesTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
                },
                order: [[0, 'desc']],
                pageLength: 10,
                responsive: true
            });

            // Form submission handling
            $('#formNotificacao').on('submit', function() {
                $(this).find('button[type="submit"]').prop('disabled', true);
                return true;
            });

            // Auto-dismiss alerts
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });
    </script>
</body>
</html>