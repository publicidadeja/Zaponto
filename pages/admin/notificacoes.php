<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
include '../../includes/db.php';
include '../../includes/admin-auth.php';

require_once '../../vendor/autoload.php';

require_once '../../logs/logger.php';


// Verificar se é admin
redirecionarSeNaoAdmin();

try {
    // Estatísticas gerais
    $stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT id) as total_notificacoes,
        COALESCE(ROUND(AVG(CASE WHEN lida = 1 THEN 1 ELSE 0 END) * 100, 2), 0) as taxa_media_leitura
    FROM notificacoes
");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalNotificacoes = $stats['total_notificacoes'];
    $taxaMediaLeitura = $stats['taxa_media_leitura'];

    // Notificações de hoje
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT id) as total 
        FROM notificacoes 
        WHERE DATE(data_criacao) = CURDATE()
    ");
    $notificacoesHoje = $stmt->fetch(PDO::FETCH_COLUMN);

    // Usuários ativos
    $stmt = $pdo->query("
        SELECT COUNT(id) as total 
        FROM usuarios 
        WHERE status = 'ativo'
    ");
    $usuariosAtivos = $stmt->fetch(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $_SESSION['erro'] = "Erro ao carregar estatísticas: " . $e->getMessage();
}

// Processar o envio de nova notificação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Inicia a transação
        $pdo->beginTransaction();
        
        // Validação e sanitização dos dados recebidos
        $titulo = trim(filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING));
        $mensagem = trim(filter_input(INPUT_POST, 'mensagem', FILTER_SANITIZE_STRING));
        $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING);
        $segmentacao = filter_input(INPUT_POST, 'segmentacao', FILTER_SANITIZE_STRING);
        $tipoEnvio = filter_input(INPUT_POST, 'tipo_envio', FILTER_SANITIZE_STRING);
        
        // Validações básicas
        if (empty($titulo) || empty($mensagem)) {
            throw new Exception('Título e mensagem são obrigatórios');
        }

        // Prepara a query base para inserção da notificação
        $stmt = $pdo->prepare("
            INSERT INTO notificacoes (
                titulo, 
                mensagem, 
                tipo, 
                data_criacao, 
                status,
                tipo_envio
            ) VALUES (
                :titulo, 
                :mensagem, 
                :tipo, 
                NOW(), 
                :status,
                :tipo_envio
            )
        ");

        // Define o status inicial
        $status = ($tipoEnvio === 'agendado') ? 'agendado' : 'enviado';

        // Executa a inserção da notificação
        $stmt->execute([
            ':titulo' => $titulo,
            ':mensagem' => $mensagem,
            ':tipo' => $tipo,
            ':status' => $status,
            ':tipo_envio' => $tipoEnvio
        ]);

        $notificacaoId = $pdo->lastInsertId();

        // Processa o agendamento se necessário
        if ($tipoEnvio === 'agendado') {
            $dataAgendamento = filter_input(INPUT_POST, 'data_agendamento', FILTER_SANITIZE_STRING);
            
            if (empty($dataAgendamento)) {
                throw new Exception('Data de agendamento é obrigatória');
            }

            $stmtAgendamento = $pdo->prepare("
                INSERT INTO agendamentos (
                    notificacao_id,
                    data_agendamento,
                    status
                ) VALUES (
                    :notificacao_id,
                    :data_agendamento,
                    'pendente'
                )
            ");

            $stmtAgendamento->execute([
                ':notificacao_id' => $notificacaoId,
                ':data_agendamento' => $dataAgendamento
            ]);
        }

        // Processa a segmentação de usuários
        $queryUsuarios = "SELECT id FROM usuarios WHERE 1=1";
        
        if ($segmentacao === 'ativos') {
            $queryUsuarios .= " AND status = 'ativo'";
        } elseif ($segmentacao === 'inativos') {
            $queryUsuarios .= " AND status = 'inativo'";
        }

        $usuarios = $pdo->query($queryUsuarios)->fetchAll(PDO::FETCH_COLUMN);

        // Insere as relações usuário-notificação
        $stmtRelacao = $pdo->prepare("
            INSERT INTO usuario_notificacao (
                usuario_id,
                notificacao_id,
                status,
                data_envio
            ) VALUES (
                :usuario_id,
                :notificacao_id,
                :status,
                :data_envio
            )
        ");

        foreach ($usuarios as $usuarioId) {
            $stmtRelacao->execute([
                ':usuario_id' => $usuarioId,
                ':notificacao_id' => $notificacaoId,
                ':status' => 'pendente',
                ':data_envio' => ($tipoEnvio === 'imediato') ? date('Y-m-d H:i:s') : null
            ]);
        }

        // Commit da transação
        $pdo->commit();
        
        // Define mensagem de sucesso
        $_SESSION['sucesso'] = "Notificação " . 
            ($tipoEnvio === 'agendado' ? 'agendada' : 'enviada') . 
            " com sucesso!";

        // Redireciona após sucesso
        header('Location: notificacoes.php');
        exit;

    } catch (Exception $e) {
        // Rollback em caso de erro
        $pdo->rollBack();
        
        // Log do erro
        error_log("Erro ao processar notificação: " . $e->getMessage());
        
        // Define mensagem de erro
        $_SESSION['erro'] = "Erro ao processar notificação: " . $e->getMessage();
        
        // Redireciona em caso de erro
        header('Location: notificacoes.php');
        exit;
    }
}

// Adicionar antes da query de busca de notificações
$filtroTipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$dataInicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$dataFim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

// Preparar parâmetros para a query
$params = [];
$whereConditions = [];

if ($filtroTipo) {
    $whereConditions[] = "n.tipo = :tipo";
    $params[':tipo'] = $filtroTipo;
}

if ($dataInicio) {
    $whereConditions[] = "n.data_criacao >= :data_inicio";
    $params[':data_inicio'] = $dataInicio . ' 00:00:00';
}

if ($dataFim) {
    $whereConditions[] = "n.data_criacao <= :data_fim";
    $params[':data_fim'] = $dataFim . ' 23:59:59';
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
    WHERE n.excluida = 0
    " . ($whereConditions ? " AND " . implode(" AND ", $whereConditions) : "") . "
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
                        <th>Ações</th>
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
                            <td>
                                <button class="btn btn-sm btn-danger excluir-notificacao" 
                                        data-id="<?php echo $notif['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

    
    <!-- Modal Nova Notificação -->
<div class="modal fade" id="modalNovaNotificacao" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Notificação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formNotificacao">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
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
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Agendamento</label>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="tipo_envio" value="imediato" id="envioImediato" checked>
                                    <label class="form-check-label" for="envioImediato">
                                        Enviar imediatamente
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="tipo_envio" value="agendado" id="envioAgendado">
                                    <label class="form-check-label" for="envioAgendado">
                                        Agendar envio
                                    </label>
                                </div>
                                <div id="campoDataAgendamento" class="mt-2 d-none">
                                    <input type="datetime-local" name="data_agendamento" class="form-control flatpickr">
                                </div>
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
                                <div class="preview-box p-3 border rounded bg-light"></div>
                            </div>
                        </div>
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

        $('#aplicarFiltros').click(function() {
    const tipo = $('#filtroTipo').val();
    const dataInicio = $('#dataInicio').val();
    const dataFim = $('#dataFim').val();
    
    let url = 'notificacoes.php?';
    if (tipo) url += `tipo=${tipo}&`;
    if (dataInicio) url += `data_inicio=${dataInicio}&`;
    if (dataFim) url += `data_fim=${dataFim}`;
    
    window.location.href = url;
});

// Exportação
$('#exportarExcel').click(function() {
    const filtros = {
        tipo: $('#filtroTipo').val(),
        data_inicio: $('#dataInicio').val(),
        data_fim: $('#dataFim').val()
    };
    
    window.location.href = `../ajax/export_notifications.php?formato=excel&${$.param(filtros)}`;
});

$('#exportarPDF').click(function() {
    const filtros = {
        tipo: $('#filtroTipo').val(),
        data_inicio: $('#dataInicio').val(),
        data_fim: $('#dataFim').val()
    };
    
    window.location.href = `../ajax/export_notifications.php?formato=pdf&${$.param(filtros)}`;
});

// Preview da notificação
$('input[name="titulo"], textarea[name="mensagem"]').on('input', function() {
    const titulo = $('input[name="titulo"]').val();
    const mensagem = $('textarea[name="mensagem"]').val();
    
    $('.preview-box').html(`
        <h5>${titulo}</h5>
        <p>${mensagem}</p>
    `);
});
    </script>

<script>
$(document).ready(function() {
    // Controle de exibição do campo de agendamento
    $('input[name="tipo_envio"]').change(function() {
        if ($(this).val() === 'agendado') {
            $('#campoDataAgendamento').removeClass('d-none');
            $('input[name="data_agendamento"]').prop('required', true);
        } else {
            $('#campoDataAgendamento').addClass('d-none');
            $('input[name="data_agendamento"]').prop('required', false);
        }
    });

    // Preview em tempo real
    $('input[name="titulo"], textarea[name="mensagem"]').on('input', function() {
        const titulo = $('input[name="titulo"]').val();
        const mensagem = $('textarea[name="mensagem"]').val();
        $('.preview-box').html(`
            <h5>${titulo || 'Título da notificação'}</h5>
            <p>${mensagem || 'Conteúdo da mensagem'}</p>
        `);
    });

    // Validação e submissão do formulário
    $('#formNotificacao').on('submit', function(e) {
    e.preventDefault();
    
    const tipoEnvio = $('input[name="tipo_envio"]:checked').val();
    const dataAgendamento = $('input[name="data_agendamento"]').val();
    
    if (tipoEnvio === 'agendado' && !dataAgendamento) {
        alert('Por favor, selecione uma data para o agendamento.');
        return false;
    }

    // Desabilitar botão para evitar duplo envio
    $(this).find('button[type="submit"]').prop('disabled', true);
    
    // Enviar formulário corretamente
    $(this).off('submit').submit();
});

// Exportação Excel
$('#exportarExcel').click(function() {
    const filtros = {
        tipo: $('#filtroTipo').val(),
        data_inicio: $('#dataInicio').val(),
        data_fim: $('#dataFim').val(),
        formato: 'excel'
    };
    
    window.location.href = '../ajax/export_notifications.php?' + $.param(filtros);
});

// Exportação PDF
$('#exportarPDF').click(function() {
    const filtros = {
        tipo: $('#filtroTipo').val(),
        data_inicio: $('#dataInicio').val(),
        data_fim: $('#dataFim').val(),
        formato: 'pdf'
    };
    
    window.location.href = '../ajax/export_notifications.php?' + $.param(filtros);
});

function validarSegmentacao($pdo, $segmentacao) {
    switch($segmentacao) {
        case 'todos':
            return true;
        case 'plano_ativo':
            return verificarUsuariosComPlanoAtivo($pdo);
        case 'plano_vencendo':
            return verificarUsuariosComPlanoVencendo($pdo);
        default:
            return false;
    }
}

function validarFiltros($filtros) {
    $filtrosValidos = [];
    
    if (!empty($filtros['tipo'])) {
        $tiposPermitidos = ['sistema', 'plano', 'aviso', 'atualizacao'];
        if (in_array($filtros['tipo'], $tiposPermitidos)) {
            $filtrosValidos['tipo'] = $filtros['tipo'];
        }
    }
    
    if (!empty($filtros['data_inicio'])) {
        if (strtotime($filtros['data_inicio'])) {
            $filtrosValidos['data_inicio'] = $filtros['data_inicio'];
        }
    }
    
    if (!empty($filtros['data_fim'])) {
        if (strtotime($filtros['data_fim'])) {
            $filtrosValidos['data_fim'] = $filtros['data_fim'];
        }
    }
    
    return $filtrosValidos;
}
</script>

<script>
$(document).ready(function() {
    // Inicializar Flatpickr para o calendário
    flatpickr(".flatpickr", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        minDate: "today",
        time_24hr: true
    });

    // Controle de exibição do campo de agendamento
    $('input[name="tipo_envio"]').change(function() {
        if ($(this).val() === 'agendado') {
            $('#campoDataAgendamento').removeClass('d-none');
            $('input[name="data_agendamento"]').prop('required', true);
        } else {
            $('#campoDataAgendamento').addClass('d-none');
            $('input[name="data_agendamento"]').prop('required', false);
        }
    });

    // Excluir notificação
    $('.excluir-notificacao').click(function() {
    const notificacaoId = $(this).data('id');
    const row = $(this).closest('tr');
    
    if (confirm('Tem certeza que deseja excluir esta notificação?')) {
        $.ajax({
            url: '../../ajax/excluir_notificacao.php',
            method: 'POST',
            data: { id: notificacaoId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    row.fadeOut(400, function() {
                        row.remove();
                    });
                    alert('Notificação excluída com sucesso!');
                } else {
                    console.error('Erro na resposta:', response);
                    alert('Erro ao excluir notificação: ' + (response.error || 'Erro desconhecido'));
                }
            },
            error: function(xhr, status, error) {
                // Log detalhado do erro
                console.error('Status:', status);
                console.error('Erro:', error);
                console.error('Resposta:', xhr.responseText);
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    alert('Erro ao excluir notificação: ' + (response.error || 'Erro desconhecido'));
                } catch (e) {
                    alert('Erro ao processar a requisição. Por favor, verifique o console para mais detalhes.');
                }
            }
        });
    }
});

    // Preview em tempo real
    $('input[name="titulo"], textarea[name="mensagem"]').on('input', function() {
        const titulo = $('input[name="titulo"]').val();
        const mensagem = $('textarea[name="mensagem"]').val();
        $('.preview-box').html(`
            <h5>${titulo || 'Título da notificação'}</h5>
            <p>${mensagem || 'Conteúdo da mensagem'}</p>
        `);
    });
});
</script>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</body>
</html>