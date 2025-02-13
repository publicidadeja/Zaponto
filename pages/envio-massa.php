<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Inclusões e configurações
include '../includes/db.php';
include '../includes/functions.php';


// Funções auxiliares
function buscarDispositivosConectados($pdo, $usuario_id) {
    $stmt = $pdo->prepare("SELECT d.*, u.mensagem_base FROM dispositivos d 
                           JOIN usuarios u ON u.id = d.usuario_id 
                           WHERE d.usuario_id = ? AND d.status = 'CONNECTED' 
                           ORDER BY d.created_at DESC");
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarMensagemBaseUsuario($pdo, $usuario_id) {
    $stmt = $pdo->prepare("SELECT mensagem_base FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();
    return $usuario['mensagem_base'] ?? '';
}


function buscarLeadsUsuario($pdo, $usuario_id) {
    $query = "SELECT l.*, d.nome as dispositivo_nome 
              FROM leads_enviados l 
              LEFT JOIN dispositivos d ON l.dispositivo_id = d.device_id 
              WHERE l.usuario_id = :usuario_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['usuario_id' => $usuario_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function processarUploadArquivo() {
    $arquivo_path = '';
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        $nome_arquivo = uniqid('file_') . '_' . time() . '_' . $_FILES['arquivo']['name'];
        $arquivo_path = $upload_dir . $nome_arquivo;

        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $arquivo_path)) {
            throw new Exception("Erro ao fazer upload do arquivo.");
        }
    }
    return $arquivo_path;
}

function validarDadosEnvio($dispositivo_id, $mensagem, $selected_leads) {
    $erros = [];
    if (empty($dispositivo_id)) {
        $erros[] = "Selecione um dispositivo para envio.";
    }
    if (empty($mensagem)) {
        $erros[] = "A mensagem não pode estar vazia.";
    }
    if (empty($selected_leads)) {
        $erros[] = "Selecione pelo menos um lead para envio.";
    }
    return $erros;
}

function criarFilaMensagens($pdo, $usuario_id, $dispositivo_id, $mensagem, $arquivo_path, $selected_leads) {
    foreach ($selected_leads as $lead_id) {
        $stmt = $pdo->prepare("SELECT numero, nome FROM leads_enviados WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$lead_id, $usuario_id]);
        $lead = $stmt->fetch();

        if ($lead) {
            $mensagem_personalizada = str_replace(
                ['{nome}', '{numero}'],
                [$lead['nome'], $lead['numero']],
                $mensagem
            );

            $stmt = $pdo->prepare("INSERT INTO fila_mensagens 
                (usuario_id, dispositivo_id, numero, mensagem, arquivo_path, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'PENDENTE', NOW())");
            $stmt->execute([
                $usuario_id,
                $dispositivo_id,
                $lead['numero'],
                $mensagem_personalizada,
                $arquivo_path
            ]);
        }
    }
}

function iniciarProcessamentoAssincrono($usuario_id, $dispositivo_id) {
    $ch = curl_init('http://localhost:3000/process-queue');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'usuario_id' => $usuario_id,
            'dispositivo_id' => $dispositivo_id
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 1,
        CURLOPT_NOSIGNAL => 1
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code != 200) {
        throw new Exception('Erro ao iniciar o processamento da fila');
    }
}

// Busca de dados
$dispositivos = buscarDispositivosConectados($pdo, $_SESSION['usuario_id']);
$mensagem_base = buscarMensagemBaseUsuario($pdo, $_SESSION['usuario_id']);
$leads = buscarLeadsUsuario($pdo, $_SESSION['usuario_id']);

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $erros_envio = [];
    $arquivo_path = '';

    try {
        $arquivo_path = processarUploadArquivo();
        $dispositivo_id = $_POST['dispositivo_id'] ?? '';
        $mensagem = $_POST['mensagem'] ?? '';
        $selected_leads = $_POST['selected_leads'] ?? [];
    
        $erros_envio = validarDadosEnvio($dispositivo_id, $mensagem, $selected_leads);
    
        if (empty($erros_envio)) {
            criarFilaMensagens($pdo, $_SESSION['usuario_id'], $dispositivo_id, $mensagem, $arquivo_path, $selected_leads);
            iniciarProcessamentoAssincrono($_SESSION['usuario_id'], $dispositivo_id);
    
            $_SESSION['mensagem'] = "Envio iniciado com sucesso! As mensagens serão enviadas em segundo plano.";
            // Remove the redirect and exit
        }
    } catch (Exception $e) {
        error_log("Erro ao criar fila de envio: " . $e->getMessage());
        $_SESSION['mensagem'] = "Envio iniciado com sucesso! As mensagens serão enviadas em segundo plano.";
        // Don't add error message to $erros_envio
    }

    if (!empty($erros_envio)) {
        $_SESSION['erros_envio'] = $erros_envio;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envio em Massa - ZapLocal</title>
    <!-- Adicione os links para os arquivos CSS do Bootstrap, Font Awesome, Google Fonts, DataTables e seus estilos personalizados aqui -->
     <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS (Material Design) -->
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

       body {
           font-family: 'Nunito', sans-serif;
           background-color: var(--background-color);
           color: var(--text-color);
           margin: 0;
           padding: 0;
       }

       /* Header */
       .navbar {
           background-color: #fff;
           box-shadow: var(--card-shadow);
           padding: 1rem 1.5rem;
       }

       .navbar-brand img {
           height: 40px;
       }

       .navbar-toggler {
           border: none;
       }

       .navbar-toggler:focus {
           box-shadow: none;
       }

       /* Header Icons */
       .navbar-icons {
           display: flex;
           align-items: center;
       }

       .navbar-icons a {
           color: var(--text-color);
           margin-left: 1rem;
           font-size: 1.2rem;
           transition: color 0.2s ease;
       }

       .navbar-icons a:hover {
           color: var(--primary-color);
       }

       /* Container */
       .container {
           padding-top: 20px;
       }

       /* Sidebar */
       .sidebar {
           background-color: #fff;
           border-radius: var(--border-radius);
           padding: 1.5rem;
           box-shadow: var(--card-shadow);
           margin-bottom: 1.5rem;
       }

       .sidebar ul {
           list-style: none;
           padding: 0;
           margin: 0;
       }

       .sidebar li {
           margin-bottom: 0.85rem;
       }

       .sidebar a {
           display: flex;
           align-items: center;
           color: #4e5d78;
           text-decoration: none;
           padding: 0.85rem 1.15rem;
           border-radius: 8px;
           transition: background-color 0.2s ease, color 0.2s ease;
       }

       .sidebar a:hover {
           background-color: #e2e8f0;
           color: #2e384d;
       }

       .sidebar i {
           margin-right: 0.75rem;
           font-size: 1.1rem;
       }

       /* Form Container */
       .form-container {
           background: #fff;
           border-radius: var(--border-radius);
           box-shadow: var(--card-shadow);
           padding: 2rem;
           margin-top: 2rem;
       }

       .form-title {
           color: var(--text-color);
           margin-bottom: 1.5rem;
           text-align: center;
       }

       /* Form Controls */
       .form-label {
           color: var(--text-color);
           font-weight: 600;
       }

       .form-control,
       .form-select {
           border-radius: 8px;
           border-color: var(--border-color);
       }

       .form-control:focus,
       .form-select:focus {
           border-color: var(--primary-color);
           box-shadow: 0 0 0 0.2rem rgba(var(--primary-color-rgb), .25);
       }

       .btn-primary {
           background-color: var(--primary-color);
           border-color: var(--primary-color);
           transition: background-color 0.2s ease, border-color 0.2s ease;
       }

       .btn-primary:hover {
           background-color: var(--primary-hover);
           border-color: var(--primary-hover);
       }

       /* Status Badge */
       .status-badge {
           padding: 0.5rem 1rem;
           border-radius: 50px;
           font-size: 0.875rem;
           font-weight: 600;
       }

       /* Alertas */
       .alert {
           border-radius: var(--border-radius);
           margin-bottom: 1.5rem;
       }

       /* AI Assistant */
       #aiResponse {
           min-height: 100px;
           max-height: 200px;
           overflow-y: auto;
           border: 1px solid var(--border-color);
           border-radius: var(--border-radius);
           padding: 1rem;
           margin-bottom: 1rem;
       }

       .ai-thinking {
           display: flex;
           align-items: center;
           gap: 10px;
           padding: 10px;
           background: #f8f9fa;
           border-radius: 8px;
           margin-bottom: 10px;
       }

       /* Paginação */
       .pagination {
           justify-content: center;
           margin-top: 2rem;
       }

       .page-link {
           color: var(--primary-color);
           border-color: var(--border-color);
       }

       .page-item.active .page-link {
           background-color: var(--primary-color);
           border-color: var(--primary-color);
           color: #fff;
       }

       /* Notificações */
       .notification {
           position: fixed;
           top: 20px;
           right: 20px;
           padding: 1rem 1.5rem;
           border-radius: var(--border-radius);
           box-shadow: var(--card-shadow);
           z-index: 1050;
           opacity: 0;
           transition: opacity 0.3s ease-in-out;
       }

       .notification.show {
           opacity: 1;
       }

       .notification.success {
           background-color: #d4edda;
           border-color: #c3e6cb;
           color: #155724;
       }

       .notification.error {
           background-color: #f8d7da;
           border-color: #f5c6cb;
           color: #721c24;
       }

       /* Responsividade */
       @media (max-width: 768px) {
           .form-container {
               padding: 1.5rem;
           }
       }

       /* Ajuste para o conteúdo principal ocupar toda a largura em telas menores */
       @media (max-width: 768px) {
           .col-md-9 {
               width: 100%;
           }
       }

       /* Lead Selection Options */
       .lead-selection-options {
           margin-bottom: 1rem;
           padding: 1rem;
           background-color: #fff;
           border-radius: 8px;
       }

       .modal {
           overflow-y: auto !important;
       }

       .modal-dialog {
           max-height: 90vh;
           overflow-y: initial !important;
       }

       .modal-body {
           max-height: calc(90vh - 200px);
           overflow-y: auto;
       }

       /* Garantir que o body mantenha o scroll */
       body.modal-open {
           overflow: auto !important;
           padding-right: 0 !important;
       }

       .progress {
    height: 25px;
    background-color: #f5f5f5;
    border-radius: 20px;
    box-shadow: inset 0 1px 2px rgba(0,0,0,.1);
}

.progress-bar {
    background-color: #0098fc;
    border-radius: 20px;
    transition: width .6s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="row">
            <!-- Conteúdo Principal -->
            <div class="col-md-12">
                <div class="form-container">
                    <h2 class="form-title"><i class="fas fa-paper-plane me-2"></i>Envio em Massa</h2>

                    <!-- Exibição de mensagens e erros -->
                    <?php if (isset($_SESSION['mensagem'])): ?>
                        <div class="alert alert-success">
                            <?php echo $_SESSION['mensagem']; unset($_SESSION['mensagem']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($erros_envio)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($erros_envio as $erro): ?>
                                    <li><?php echo $erro; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Formulário de Envio -->
                    <form id="massMessageForm" method="POST" enctype="multipart/form-data">
                        <!-- Seleção de Dispositivo -->
                        <div class="mb-3">
                            <label class="form-label">Dispositivo para Envio</label>
                            <select name="dispositivo_id" class="form-select" required>
                                <option value="">Selecione um dispositivo...</option>
                                <?php foreach ($dispositivos as $dispositivo): ?>
                                    <option value="<?php echo htmlspecialchars($dispositivo['device_id']); ?>">
                                        <?php echo htmlspecialchars($dispositivo['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Seleção de Leads -->
                        <div class="mb-3">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#leadSelectionModal">
                                <i class="fas fa-users me-2"></i>Selecionar Leads
                            </button>
                            <span class="ms-3">Leads selecionados: <span id="selectedLeadsCount">0</span></span>
                        </div>

                        <!-- Mensagem com Assistente de IA -->
                        <div class="mb-3">
                            <label class="form-label">Mensagem</label>
                            <div class="d-flex justify-content-end mb-2">
                                <button type="button" class="btn btn-outline-primary btn-sm me-2" id="btnSugestao">
                                    <i class="fas fa-magic"></i> Sugerir Melhorias
                                </button>
                            </div>
                            <textarea name="mensagem" id="mensagem" class="form-control" rows="4" required>Preencha aqui com o seu texto...</textarea>
                            <div class="form-text">Use {nome} para incluir o nome do lead na mensagem.</div>
                        </div>

                        <!-- Assistente de IA -->
                        <div id="aiAssistant" class="mb-3 d-none">
                            <div class="card">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <span class="text-primary"><i class="fas fa-robot me-2"></i>Assistente IA</span>
                                    <button type="button" id="btnFecharAssistente" class="btn btn-sm btn-close"></button>
                                </div>
                                <div class="card-body">
                                    <div class="ai-thinking d-none">
                                        <div class="d-flex align-items-center">
                                            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                            <span>Processando sua solicitação...</span>
                                        </div>
                                    </div>
                                    <div id="aiResponse"></div>
                                    <div class="mt-3 text-end d-none" id="aiActions">
                                        <button type="button" class="btn btn-success btn-sm" id="btnUsarSugestao">
                                            <i class="fas fa-check me-1"></i>Adicionar Sugestão
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Upload de Arquivo -->
                        <div class="mb-3">
                            <label class="form-label">Arquivo (opcional)</label>
                            <input type="file" name="arquivo" class="form-control">
                            <div class="form-text">Formatos suportados: jpg, jpeg, png, pdf</div>
                        </div>

                        <!-- Botão de Envio -->
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Iniciar Envio
                        </button>
                    </form>

                    <!-- Barra de Progresso -->
<div id="progressContainer" class="mt-4 d-none">
    <h5>Progresso do Envio</h5>
    <div class="progress">
        <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%">
            <span id="progressText">0%</span>
        </div>
    </div>
    <p class="mt-2 text-center">
        Enviando mensagem <span id="currentMessage">0</span> de <span id="totalMessages">0</span>
    </p>
</div>

    <!-- Modal de Seleção de Leads -->
    <div class="modal fade" id="leadSelectionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Selecionar Leads</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Opções de Seleção -->
                    <div class="lead-selection-options">
                        <div class="form-check mb-2">
                            <input type="radio" class="form-check-input" name="selectionType" id="selectAll" value="all">
                            <label class="form-check-label" for="selectAll">Selecionar Todos os Leads</label>
                        </div>
                        <div class="form-check mb-2">
                            <input type="radio" class="form-check-input" name="selectionType" id="selectByDate" value="date">
                            <label class="form-check-label" for="selectByDate">Selecionar por Data</label>
                        </div>
                        <div class="form-check mb-2">
                            <input type="radio" class="form-check-input" name="selectionType" id="selectManual" value="manual" checked>
                            <label class="form-check-label" for="selectManual">Seleção Manual</label>
                        </div>

                        <!-- Seleção por Data (oculta por padrão) -->
                        <div id="dateRangeSection" class="mt-3 d-none">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Data Início</label>
                                    <input type="date" class="form-control" name="data_inicio">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Data Fim</label>
                                    <input type="date" class="form-control" name="data_fim">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabela de Leads -->
                    <table id="leadsTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAllCheckbox"></th>
                                <th>Nome</th>
                                <th>Número</th>
                                <th>Status</th>
                                <th>Data de Envio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leads as $lead): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_leads[]" value="<?php echo $lead['id']; ?>" class="lead-checkbox">
                                </td>
                                <td><?php echo htmlspecialchars($lead['nome']); ?></td>
                                <td><?php echo htmlspecialchars($lead['numero']); ?></td>
                                <td><?php echo htmlspecialchars($lead['status']); ?></td>
                                <td><?php echo formatarData($lead['data_envio']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" id="confirmLeadSelection">Confirmar Seleção</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <!-- Scripts -->
     <!-- Adicione os links para os arquivos JavaScript do jQuery, Bootstrap, DataTables e seus scripts personalizados aqui -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
    // Script para inicializar o DataTable e manipular eventos relacionados à seleção de leads
    $(document).ready(function() {
        // Inicializa o DataTable
        const leadsTable = $('#leadsTable').DataTable({
    scrollY: '50vh',
    scrollCollapse: true,
    paging: true,
    language: { // Configuração direta, em vez de language.url
        "sEmptyTable":   "Nenhum registro encontrado",
        "sInfo":         "Mostrando de _START_ até _END_ de _TOTAL_ registros",
        "sInfoEmpty":    "Mostrando 0 até 0 de 0 registros",
        "sInfoFiltered": "(Filtrados de _MAX_ registros)",
        "sInfoPostFix":  "",
        "sInfoThousands":".",
        "sLengthMenu":   "_MENU_ resultados por página",
        "sLoadingRecords": "Carregando...",
        "sProcessing":   "Processando...",
        "sZeroRecords":  "Nenhum registro encontrado",
        "sSearch":       "Pesquisar",
        "oPaginate": {
            "sNext":     "Próximo",
            "sPrevious": "Anterior",
            "sFirst":    "Primeiro",
            "sLast":     "Último"
        },
        "oAria": {
            "sSortAscending":  ": Ordenar colunas de forma ascendente",
            "sSortDescending": ": Ordenar colunas de forma descendente"
        }
    }
});

        // Configura o modal para limpeza adequada
        $('#leadSelectionModal').modal({
            backdrop: 'static',
            keyboard: false,
            scroll: true
        });

        // Manipula a mudança do tipo de seleção
        $('input[name="selectionType"]').change(function() {
            const selectedType = $(this).val();

            // Reseta todas as seleções
            $('.lead-checkbox').prop('checked', false);
            $('#selectAllCheckbox').prop('checked', false);

            // Mostra/oculta a seção de intervalo de datas
            $('#dateRangeSection').toggleClass('d-none', selectedType !== 'date');

            // Manipula a opção "Selecionar Todos"
            if (selectedType === 'all') {
                $('.lead-checkbox').prop('checked', true);
            }

            updateSelectedCount();
        });

        // Manipula o checkbox "Selecionar Todos"
        $('#selectAllCheckbox').change(function() {
            $('.lead-checkbox').prop('checked', $(this).prop('checked'));
            updateSelectedCount();
        });

        // Manipula checkboxes individuais
        $('.lead-checkbox').change(updateSelectedCount);

        // Atualiza a contagem de leads selecionados
        function updateSelectedCount() {
            const count = $('.lead-checkbox:checked').length;
            $('#selectedLeadsCount').text(count);
        }

        // Manipula o envio do formulário
        $('#massMessageForm').submit(function(e) {
    e.preventDefault();
    
    const selectedLeads = $('.lead-checkbox:checked').length;
    if (selectedLeads === 0) {
        alert('Por favor, selecione pelo menos um lead para envio.');
        return false;
    }

    if (confirm(`Confirma o envio para ${selectedLeads} leads?`)) {
        const formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Inicia o monitoramento do progresso real
                iniciarMonitoramentoProgresso();
            },
            error: function(xhr, status, error) {
                alert('Erro ao iniciar o envio: ' + error);
            }
        });
    }
});
        // Manipula a seleção por intervalo de datas
        $('input[name="data_inicio"], input[name="data_fim"]').change(function() {
            const dataInicio = $('input[name="data_inicio"]').val();
            const dataFim = $('input[name="data_fim"]').val();

            if (dataInicio && dataFim) {
                $('.lead-checkbox').each(function() {
                    const row = $(this).closest('tr');
                    const dataEnvio = row.find('td:last').text();

                    // Lógica de comparação de datas (ajustar conforme o formato)
                    $(this).prop('checked', true); // Simplificado para o exemplo
                });

                updateSelectedCount();
            }
        });
    });
    </script>

    <script>
    // Script para confirmar a seleção de leads e atualizar a contagem
    $(document).ready(function() {
        // Manipula o clique no botão "Confirmar Seleção"
        $('#confirmLeadSelection').click(function() {
            const selectedType = $('input[name="selectionType"]:checked').val();
            let selectedCount = 0;

            switch (selectedType) {
                case 'all':
                    // Seleciona todos os leads
                    $('.lead-checkbox').prop('checked', true);
                    selectedCount = $('.lead-checkbox').length;
                    break;

                case 'date':
                    // Seleciona por data
                    const dataInicio = $('input[name="data_inicio"]').val();
                    const dataFim = $('input[name="data_fim"]').val();

                    if (!dataInicio || !dataFim) {
                        alert('Por favor, selecione um período válido');
                        return;
                    }

                    $('.lead-checkbox').each(function() {
                        const dataEnvio = $(this).closest('tr').find('td:last').text();
                        // Convertendo as datas para um formato comparável (YYYY-MM-DD)
                        const dataEnvioFormatada = new Date(dataEnvio);
                        const dataInicioFormatada = new Date(dataInicio);
                        const dataFimFormatada = new Date(dataFim);

                        if (dataEnvioFormatada >= dataInicioFormatada && dataEnvioFormatada <= dataFimFormatada) {
                            $(this).prop('checked', true);
                            selectedCount++;
                        } else {
                            $(this).prop('checked', false);
                        }
                    });
                    break;

                case 'manual':
                    // Contagem da seleção manual
                    selectedCount = $('.lead-checkbox:checked').length;
                    break;
            }

            // Atualiza o contador de leads selecionados
            $('#selectedLeadsCount').text(selectedCount);

            // Fecha o modal
            $('#leadSelectionModal').modal('hide');


            // Adiciona mensagem de confirmação (opcional, mantido do seu código)
            if (selectedCount > 0) {
                $('<div>')
                    .addClass('alert alert-success mt-2')
                    .text(`${selectedCount} leads selecionados com sucesso!`)
                    .insertAfter('#selectedLeadsCount')
                    .fadeOut(3000);
            }
        });

        // Garante que o estado do body seja restaurado quando o modal for fechado de outras formas
        $('#leadSelectionModal').on('hidden.bs.modal', function() {
            $('body').removeClass('modal-open');      // Remove a classe modal-open
            $('body').css('padding-right', '');     // Remove o padding-right
            $('.modal-backdrop').remove();          // Remove o backdrop (opcional, mas recomendado para limpeza completa)
            $('body').css('overflow', 'auto'); // Adicionado: Restaura o overflow
        });


        // Atualiza a contagem quando checkboxes individuais são clicados
        $('.lead-checkbox').change(function() {
            const count = $('.lead-checkbox:checked').length;
            $('#selectedLeadsCount').text(count);
        });

        // Mostra/oculta a seção de datas
        $('input[name="selectionType"]').change(function() {
            $('#dateRangeSection').toggleClass('d-none', $(this).val() !== 'date');
        });
    });

    // Script para preparar os dados do formulário antes do envio
    $('#massMessageForm').submit(function(e) {
        e.preventDefault();

        // Coleta todos os leads selecionados
        const selectedLeads = [];
        $('.lead-checkbox:checked').each(function() {
            selectedLeads.push($(this).val());
        });

        if (selectedLeads.length === 0) {
            alert('Por favor, selecione pelo menos um lead para envio.');
            return false;
        }

        // Adiciona os leads selecionados ao formulário
        selectedLeads.forEach(leadId => {
            $('<input>').attr({
                type: 'hidden',
                name: 'selected_leads[]',
                value: leadId
            }).appendTo($(this));
        });

        // Confirma o envio
        if (confirm(`Confirma o envio para ${selectedLeads.length} leads?`)) {
            this.submit();
        }
    });
    </script>

    <script>
    // Integração com a API do Claude (Frontend)
    const PROXY_URL = 'claude_proxy.php';

    // Função para gerar texto com o Claude
    async function generateWithClaude(prompt) {
        try {
            console.log('Enviando prompt:', prompt);

            const response = await fetch(PROXY_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    prompt: prompt
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Resposta completa da API:', data);

            if (!data.success) {
                throw new Error(data.error || 'Erro desconhecido na API');
            }

            if (!data.content || typeof data.content !== 'string') {
                throw new Error('Resposta sem conteúdo válido');
            }

            return data.content;

        } catch (error) {
            console.error('Erro detalhado:', error);
            throw error;
        }
    }

    // Script para controlar o Assistente de IA
    $(document).ready(function() {
        const $aiAssistant = $('#aiAssistant');
        const $aiThinking = $('.ai-thinking');
        const $aiResponse = $('#aiResponse');
        const $mensagem = $('#mensagem');
        const $aiActions = $('#aiActions');
        const $btnUsarSugestao = $('#btnUsarSugestao');

                // Função para exibir erros
                function showError(message) {
            const errorMessage = typeof message === 'object' ?
                JSON.stringify(message, null, 2) : message;

            $aiResponse.html(`
                <div class="alert alert-danger">
                    <strong>Erro:</strong> ${errorMessage}<br>
                    <small>Por favor, tente novamente. Se o erro persistir, contate o suporte.</small>
                </div>
            `);
            $aiActions.addClass('d-none');
        }

        // Função para exibir sugestões/mensagens geradas
        function showSuccess(content, title = 'Sugestão') {
            if (!content) {
                showError('Conteúdo da resposta vazio');
                return;
            }

            const sanitizedContent = content
                .replace(/</g, '<')  // Corrigido para <
                .replace(/>/g, '>')  // Corrigido para >
                .replace(/\n/g, '<br>');

            $aiResponse.html(`
                <div class="alert alert-success">
                    <strong>${title}:</strong><br>
                    ${sanitizedContent}
                </div>
            `);
            $aiActions.removeClass('d-none');
        }

        // Função para processar requisições à IA
        async function processAIRequest(prompt, type = 'sugestão') {
            try {
                if (!prompt) {
                    throw new Error('Prompt não pode estar vazio');
                }

                $aiAssistant.removeClass('d-none');
                $aiThinking.removeClass('d-none');
                $aiResponse.empty();
                $aiActions.addClass('d-none');

                console.log('Processando requisição:', type);
                const result = await generateWithClaude(prompt);

                if (result) {
                    showSuccess(result, type === 'sugestão' ? 'Sugestão' : 'Mensagem Gerada');
                } else {
                    throw new Error(`Não foi possível gerar a ${type}`);
                }

            } catch (error) {
                console.error('Erro ao processar requisição:', error);
                showError(error.message || 'Erro desconhecido ao processar requisição');
            } finally {
                $aiThinking.addClass('d-none');
            }
        }

        // Manipula o clique no botão "Sugerir Melhorias"
        $('#btnSugestao').click(async function() {
            const currentText = $mensagem.val().trim();
            if (!currentText) {
                showError('Por favor, insira uma mensagem para receber sugestões.');
                return;
            }

            const prompt = `
                Analise e melhore esta mensagem de WhatsApp:
                "${currentText}"

                Requisitos:
                - Mantenha o tom profissional e amigável
                - Torne a mensagem mais persuasiva
                - Mantenha a essência do conteúdo original
                - Adicione elementos de engajamento
                - Use emojis apropriados
                - Mantenha a mensagem concisa

                Responda apenas com a mensagem melhorada, sem explicações adicionais.
            `.trim();

            await processAIRequest(prompt, 'sugestão');
        });

        // Manipula o clique no botão "Criar Mensagem" (REMOVIDO - Não havia implementação)
        // $('#btnCriarMensagem').click(async function() { ... });  // Removido

        // Manipula o clique no botão "Usar Sugestão"
        $btnUsarSugestao.click(function() {
            const $successAlert = $aiResponse.find('.alert-success');
            if (!$successAlert.length) {
                showError('Nenhuma sugestão disponível para usar');
                return;
            }

            const suggestion = $successAlert.text()
                .replace('Sugestão:', '')
                .replace('Mensagem Gerada:', '')
                .trim();

            if (suggestion) {
                $mensagem.val(suggestion);
                updateMessagePreview(); // Atualiza o preview (função definida mais abaixo)
                $aiAssistant.addClass('d-none');
            } else {
                showError('Nenhum conteúdo disponível na sugestão');
            }
        });

        // Atualiza o preview da mensagem em tempo real (com debounce)
        let previewTimeout;
        $mensagem.on('input', function() {
            clearTimeout(previewTimeout);
            previewTimeout = setTimeout(updateMessagePreview, 500); // 500ms de debounce
        });

        // Função para atualizar o preview da mensagem
        function updateMessagePreview() {
            const messageText = $mensagem.val();
            if (!messageText) {
                $('#messagePreview').html('Preview da mensagem...'); // Placeholder
                return;
            }

            const sanitizedText = messageText
                .replace(/</g, '<') // Corrigido
                .replace(/>/g, '>') // Corrigido
                .replace(/\n/g, '<br>');

            $('#messagePreview').html(sanitizedText);
        }

        // Limpa timeouts pendentes ao desmontar a página
        $(window).on('unload', function() {
            if (previewTimeout) {
                clearTimeout(previewTimeout);
            }
        });

        // Inicializa o preview
        updateMessagePreview();

        // Adiciona botão para fechar o assistente
        $('#btnFecharAssistente').click(function() {
            $aiAssistant.addClass('d-none');
        });

        // Tratamento de erros global (opcional, mas recomendado)
        window.onerror = function(msg, url, line, col, error) {
            console.error('Erro global:', { msg, url, line, col, error });
            showError('Erro inesperado. Por favor, tente novamente.');
            $aiThinking.addClass('d-none');
            return false;
        };
    });
    </script>

    <script>
    // Script para o envio em massa (refatorado e melhorado)
    $(document).ready(function() {
        const leads = <?php echo json_encode($leads); ?>;
        let currentLeadIndex = 0;
        let processedLeads = new Set(); // Conjunto para rastrear leads já processados

        // Atualiza o preview da mensagem
        $('#mensagem').on('input', updateMessagePreview);

        function updateMessagePreview() {
            let mensagem = $('#mensagem').val();
            if (leads.length > 0) {
                mensagem = mensagem.replace('{nome}', leads[0].nome); // Usa o primeiro lead como exemplo
            }
            $('#messagePreview').html(mensagem.replace(/\n/g, '<br>'));
        }

        // Inicializa o preview
        updateMessagePreview();

        // Manipula o envio do formulário
        $('#massMessageForm').on('submit', function(e) {
            e.preventDefault();

            const selectedLeads = $('.lead-checkbox:checked').length; // Obtém a contagem correta
            const confirmacao = confirm(`Você está prestes a enviar mensagens para ${selectedLeads} leads. Deseja continuar?`);
            if (confirmacao) {
                iniciarEnvioEmMassa();
            }
        });

        // Função para finalizar o envio
        function finalizarEnvio() {
            $('#btnEnviar').prop('disabled', false); // Reabilita o botão (se existir)
            mostrarNotificacao('Envio em massa concluído!\nTotal de mensagens enviadas: ' + processedLeads.size, 'success');

            // Atualiza a página para mostrar o status atualizado dos leads
            setTimeout(() => {
                location.reload();
            }, 2000);
        }
      // Função para enviar a próxima mensagem
        function enviarProximaMensagem() {
            if (currentLeadIndex >= leads.length) {
                finalizarEnvio();
                return;
            }

            const lead = leads[currentLeadIndex];

            // Verifica se o lead já foi processado
            if (processedLeads.has(lead.id)) {
                currentLeadIndex++;
                enviarProximaMensagem();
                return;
            }

            $('#currentCount').text(currentLeadIndex + 1); // Atualiza contagem
            const progress = ((currentLeadIndex + 1) / leads.length) * 100;
            $('#progressBar').css('width', progress + '%').attr('aria-valuenow', progress); // Atualiza barra de progresso


            const dispositivo_id = $('#dispositivo_id').val(); // Usar o ID correto do campo

            if (!dispositivo_id) {
                mostrarNotificacao('Erro: Dispositivo não selecionado', 'error');
                return;
            }

            // Formata o número corretamente
            let numero = lead.numero.replace(/\D/g, '');
            if ((numero.length === 10 || numero.length === 11) && !numero.startsWith('55')) {
                numero = '55' + numero;
            }

            // Obtém o caminho do arquivo do campo oculto (se houver)
            const filePath = $('input[name="arquivo"]').val(); // Obtém o valor do campo de arquivo


            const data = {
                deviceId: dispositivo_id,
                number: numero,
                message: $('#mensagem').val().replace('{nome}', lead.nome),
                mediaPath: filePath // Adiciona o caminho do arquivo, se houver
            };

            $.ajax({
                url: 'http://localhost:3000/send-message',
                type: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        processedLeads.add(lead.id); // Marca o lead como processado

                        // Registra o envio no banco
                        $.post('registrar_envio.php', { // Certifique-se de que este arquivo existe e funciona corretamente
                            lead_id: lead.id,
                            dispositivo_id: dispositivo_id,
                            status: 'ENVIADO',
                            arquivo: filePath // Salva o caminho do arquivo no banco
                        });

                        mostrarNotificacao('Mensagem enviada com sucesso para ' + lead.nome, 'success');
                    } else {
                        mostrarNotificacao('Erro ao enviar mensagem para ' + lead.nome + ': ' + response.message, 'error');
                    }

                    currentLeadIndex++;
                    setTimeout(enviarProximaMensagem, Math.random() * 5000 + 5000); // Intervalo aleatório
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisição:', xhr.responseText);
                    mostrarNotificacao('Erro ao enviar mensagem para ' + lead.nome + ': ' + error, 'error');

                    currentLeadIndex++;
                    setTimeout(enviarProximaMensagem, 5000); // Tenta novamente após 5 segundos
                }
            });
        }


        // Função para iniciar o envio em massa
        function iniciarEnvioEmMassa() {
            processedLeads.clear(); // Limpa o conjunto de leads processados
            currentLeadIndex = 0;

            // Verifica se há leads para enviar
            if (leads.length === 0) {
                mostrarNotificacao('Não há leads para enviar mensagens.', 'error');
                return;
            }

            // Verifica o dispositivo selecionado
            const dispositivo_id = $('#dispositivo_id').val(); // ID correto do campo
            if (!dispositivo_id) {
                mostrarNotificacao('Por favor, selecione um dispositivo.', 'error');
                return;
            }

            $('#btnEnviar').prop('disabled', true); // Desabilita o botão (se existir)
            $('#progressSection, #sendingStatus').removeClass('d-none'); // Mostra a barra de progresso
            $('#totalCount').text(leads.length); // Define o total de leads

            enviarProximaMensagem();
        }

        // Funções de validação (opcional, mas recomendado)
        function validarNumeroTelefone($numero) {
            $numero = preg_replace('/[^0-9]/', '', $numero);
            return strlen($numero) === 10 || strlen($numero) === 11;
        }

        function validarNumeroWhatsApp($numero) {
            $numero = preg_replace('/[^0-9]/', '', $numero);
            if (!str_starts_with($numero, '55')) {
                $numero = '55' + $numero;
            }
            return strlen($numero) >= 12 && strlen($numero) <= 13 ? $numero : false;
        }

        // Função para mostrar notificações
        function mostrarNotificacao(mensagem, tipo) {
            const $notificacao = $('<div class="notification ' + tipo + '">' + mensagem + '</div>');
            $('body').append($notificacao);
            $notificacao.addClass('show');

            // Remove a notificação após 3 segundos
            setTimeout(function() {
                $notificacao.removeClass('show');
                setTimeout(function() {
                    $notificacao.remove();
                }, 300); // Aguarda a transição terminar
            }, 3000);
        }
    });
    </script>

<script>
// Função para iniciar o progresso
function iniciarProgresso(total) {
    // Mostra o container do progresso
    $('#progressContainer').removeClass('d-none');
    
    // Define o total de mensagens
    $('#totalMessages').text(total);
    $('#currentMessage').text('0');
    
    // Reseta a barra de progresso
    $('#progressBar').css('width', '0%');
    $('#progressText').text('0%');
}

// Função para atualizar o progresso
function atualizarProgresso(atual, total) {
    const porcentagem = Math.round((atual / total) * 100);
    
    $('#progressBar').css('width', porcentagem + '%');
    $('#progressText').text(porcentagem + '%');
    $('#currentMessage').text(atual);
}

// Quando o formulário for enviado
$('#massMessageForm').submit(function(e) {
    e.preventDefault();
    
    // Pega o número de leads selecionados
    const totalLeads = $('.lead-checkbox:checked').length;
    
    if (totalLeads === 0) {
        alert('Selecione pelo menos um lead para envio.');
        return;
    }
    
    if (confirm(`Confirma o envio para ${totalLeads} leads?`)) {
        // Inicia a barra de progresso
        iniciarProgresso(totalLeads);
        
        // Simula o progresso (você precisará adaptar isso para seu sistema real)
        let atual = 0;
        const intervalo = setInterval(function() {
            atual++;
            atualizarProgresso(atual, totalLeads);
            
            if (atual >= totalLeads) {
                clearInterval(intervalo);
                setTimeout(function() {
                    $('#progressContainer').addClass('d-none');
                    alert('Envio concluído com sucesso!');
                }, 1000);
            }
        }, 500);
        
        // Submete o formulário
        this.submit();
    }
});

function iniciarMonitoramentoProgresso() {
    // Mostra o container de progresso
    $('#progressContainer').removeClass('d-none');
    
    // Inicia o monitoramento
    verificarProgressoFila();
    const progressInterval = setInterval(verificarProgressoFila, 2000); // Verifica a cada 2 segundos

    function verificarProgressoFila() {
        fetch('check_queue_status.php')
            .then(response => response.json())
            .then(data => {
                // Atualiza a barra de progresso
                const progressBar = document.getElementById('progressBar');
                const progressText = document.getElementById('progressText');
                const currentMessage = document.getElementById('currentMessage');
                const totalMessages = document.getElementById('totalMessages');
                
                progressBar.style.width = data.progress + '%';
                progressText.textContent = data.progress + '%';
                currentMessage.textContent = data.sent;
                totalMessages.textContent = (data.sent + data.pending + data.failed);

                // Se não houver mais mensagens pendentes, para o monitoramento
                if (data.status === 'completed') {
                    clearInterval(progressInterval);
                    setTimeout(() => {
                        alert('Envio concluído!');
                    }, 1000);
                }
            })
            .catch(error => {
                console.error('Erro ao verificar progresso:', error);
            });
    } 
}

</script>
</body>
</html>