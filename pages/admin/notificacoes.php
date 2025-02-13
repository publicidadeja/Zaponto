<?php
session_start();
include '../../includes/db.php';
include '../../includes/admin-auth.php';

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
            COUNT(CASE WHEN n.lida = 1 THEN 1 END) as total_lidas
        FROM notificacoes n
        GROUP BY n.id, n.tipo, n.titulo, n.mensagem, n.data_criacao
        ORDER BY n.data_criacao DESC
        LIMIT 100
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