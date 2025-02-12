<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/db.php';
include '../includes/functions.php';

// Fetch connected devices
$stmt = $pdo->prepare("SELECT d.*, u.mensagem_base FROM dispositivos d 
                       JOIN usuarios u ON u.id = d.usuario_id 
                       WHERE d.usuario_id = ? AND d.status = 'CONNECTED' 
                       ORDER BY d.created_at DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$dispositivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's base message
$stmt = $pdo->prepare("SELECT mensagem_base FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();
$mensagem_base = $usuario['mensagem_base'] ?? '';

// Fetch all leads for the user
$query = "SELECT l.*, d.nome as dispositivo_nome 
          FROM leads_enviados l 
          LEFT JOIN dispositivos d ON l.dispositivo_id = d.device_id 
          WHERE l.usuario_id = :usuario_id";
$stmt = $pdo->prepare($query);
$stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $arquivo_path = '';
    $erros_envio = []; // Inicializa o array de erros

    // Processamento do arquivo
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        $nome_arquivo = uniqid('file_') . '_' . time() . '_' . $_FILES['arquivo']['name'];
        $arquivo_path = $upload_dir . $nome_arquivo;
        
        // Verificar e criar diretório de upload se não existir
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Tentar fazer o upload do arquivo
        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $arquivo_path)) {
            // Upload bem sucedido
        } else {
            $erros_envio[] = "Erro ao fazer upload do arquivo.";
            $arquivo_path = ''; // Reset do caminho em caso de erro
        }
    }

    $dispositivo_id = $_POST['dispositivo_id'] ?? '';
    $mensagem = $_POST['mensagem'] ?? '';
    $selected_leads = $_POST['selected_leads'] ?? [];
    
    // Validações iniciais
    if (empty($dispositivo_id)) {
        $erros_envio[] = "Selecione um dispositivo para envio.";
    }
    if (empty($mensagem)) {
        $erros_envio[] = "A mensagem não pode estar vazia.";
    }
    if (empty($selected_leads)) {
        $erros_envio[] = "Selecione pelo menos um lead para envio.";
    }

    if (empty($erros_envio)) {
        // Buscar os leads selecionados do banco de dados
        $placeholders = str_repeat('?,', count($selected_leads) - 1) . '?';
        $stmt = $pdo->prepare("SELECT * FROM leads_enviados 
                              WHERE id IN ($placeholders) 
                              AND usuario_id = ?");
        
        $params = array_merge($selected_leads, [$_SESSION['usuario_id']]);
        $stmt->execute($params);
        $leads_to_process = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $success_count = 0;
        $error_count = 0;

        foreach ($leads_to_process as $lead) {
            try {
                // Personalizar mensagem
                $mensagem_personalizada = str_replace(
                    ['{nome}', '{numero}'],
                    [$lead['nome'], $lead['numero']],
                    $mensagem
                );

                // Preparar dados para envio
                $data = [
                    'deviceId' => $dispositivo_id,
                    'number' => formatarNumeroWhatsApp($lead['numero']),
                    'message' => $mensagem_personalizada
                ];

                // Adicionar arquivo se existir
                if (!empty($arquivo_path) && file_exists($arquivo_path)) {
                    $data['mediaPath'] = $arquivo_path;
                }

                // Enviar mensagem
                $ch = curl_init('http://localhost:3000/send-message');
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($data),
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_RETURNTRANSFER => true
                ]);

                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if ($http_code == 200) {
                    // Atualizar status do lead
                    $stmt = $pdo->prepare("UPDATE leads_enviados SET 
                        status = 'ENVIADO',
                        data_envio = NOW()
                        WHERE id = ?");
                    $stmt->execute([$lead['id']]);
                    
                    $success_count++;
                    
                    // Intervalo entre mensagens
                    sleep(rand(2, 5));
                } else {
                    throw new Exception('Erro ao enviar mensagem');
                }

                curl_close($ch);

            } catch (Exception $e) {
                $error_count++;
                error_log("Erro ao enviar mensagem para {$lead['numero']}: " . $e->getMessage());
                $erros_envio[] = "Erro ao enviar mensagem para {$lead['numero']}: " . $e->getMessage();
            }
        }

        $_SESSION['mensagem'] = "Envio concluído: $success_count mensagens enviadas, $error_count falhas.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envio em Massa - ZapLocal</title>
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

                    <?php if (isset($_SESSION['mensagem'])): ?>
                        <div class="alert alert-success">
                            <?php 
                            echo $_SESSION['mensagem'];
                            unset($_SESSION['mensagem']);
                            ?>
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

                    <form id="massMessageForm" method="POST" enctype="multipart/form-data">
                        <!-- Device Selection -->
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

                        <!-- Lead Selection Button -->
                        <div class="mb-3">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#leadSelectionModal">
                                <i class="fas fa-users me-2"></i>Selecionar Leads
                            </button>
                            <span class="ms-3">Leads selecionados: <span id="selectedLeadsCount">0</span></span>
                        </div>

                        <!-- Message Input with AI Assistant -->
                        <div class="mb-3">
                            <label class="form-label">Mensagem</label>
                            <div class="d-flex justify-content-end mb-2">
                                <button type="button" class="btn btn-outline-primary btn-sm me-2" id="btnSugestao">
                                    <i class="fas fa-magic"></i> Sugerir Melhorias
                                </button>
                            </div>
                            <textarea name="mensagem" id="mensagem" class="form-control" rows="4" required><?php echo htmlspecialchars($mensagem_base); ?></textarea>
                            <div class="form-text">Use {nome} para incluir o nome do lead na mensagem.</div>
                        </div>

                        <!-- AI Assistant -->
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

                        <!-- File Upload -->
                        <div class="mb-3">
                            <label class="form-label">Arquivo (opcional)</label>
                            <input type="file" name="arquivo" class="form-control">
                            <div class="form-text">Formatos suportados: jpg, jpeg, png, pdf</div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Iniciar Envio
                        </button>
                    </form>

                    <!-- Progress Bar (hidden by default) -->
                    <div id="progressSection" class="mt-4 d-none">
                        <h5>Progresso do Envio</h5>
                        <div class="progress">
                            <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <p class="mt-2">Enviando mensagem <span id="currentCount">0</span> de <span id="totalCount">0</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lead Selection Modal -->
    <div class="modal fade" id="leadSelectionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Selecionar Leads</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Selection Options -->
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

                        <!-- Date Range Selection (initially hidden) -->
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

                    <!-- Leads Table -->
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const leadsTable = $('#leadsTable').DataTable({
    scrollY: '50vh', // Altura máxima da tabela
    scrollCollapse: true,
    paging: true,
    language: {
        url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
    }
});

            // Garantir limpeza adequada quando o modal for fechado
            $('#leadSelectionModal').modal({
    backdrop: 'static',
    keyboard: false,
    scroll: true // Permite scroll
});

            // Handle selection type change
            $('input[name="selectionType"]').change(function() {
                const selectedType = $(this).val();
                
                // Reset all selections
                $('.lead-checkbox').prop('checked', false);
                $('#selectAllCheckbox').prop('checked', false);
                
                // Show/hide date range section
                if (selectedType === 'date') {
                    $('#dateRangeSection').removeClass('d-none');
                } else {
                    $('#dateRangeSection').addClass('d-none');
                }
                
                // Handle "Select All" option
                if (selectedType === 'all') {
                    $('.lead-checkbox').prop('checked', true);
                }
                
                updateSelectedCount();
            });

            // Handle "Select All" checkbox
            $('#selectAllCheckbox').change(function() {
                $('.lead-checkbox').prop('checked', $(this).prop('checked'));
                updateSelectedCount();
            });

            // Handle individual checkboxes
            $('.lead-checkbox').change(function() {
                updateSelectedCount();
            });

            // Update selected count
            function updateSelectedCount() {
                const count = $('.lead-checkbox:checked').length;
                $('#selectedLeadsCount').text(count);
            }

            // Handle form submission
            $('#massMessageForm').submit(function(e) {
                e.preventDefault();
                
                const selectedLeads = $('.lead-checkbox:checked').length;
                if (selectedLeads === 0) {
                    alert('Por favor, selecione pelo menos um lead para envio.');
                    return false;
                }

                if (confirm(`Confirma o envio para ${selectedLeads} leads?`)) {
                    // Show progress section
                    $('#progressSection').removeClass('d-none');
                    $('#totalCount').text(selectedLeads);
                    
                    // Submit the form
                    this.submit();
                }
            });

            // Handle date range selection
            $('input[name="data_inicio"], input[name="data_fim"]').change(function() {
                const dataInicio = $('input[name="data_inicio"]').val();
                const dataFim = $('input[name="data_fim"]').val();
                
                if (dataInicio && dataFim) {
                    $('.lead-checkbox').each(function() {
                        const row = $(this).closest('tr');
                        const dataEnvio = row.find('td:last').text();
                        
                        // Compare dates and check/uncheck accordingly
                        // Note: You'll need to adjust the date comparison logic based on your date format
                        $(this).prop('checked', true); // Simplified for example
                    });
                    
                    updateSelectedCount();
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Manipular o clique no botão "Confirmar Seleção"
            $('#confirmLeadSelection').click(function() {
                const selectedType = $('input[name="selectionType"]:checked').val();
                let selectedCount = 0;
                
                switch(selectedType) {
                    case 'all':
                        // Selecionar todos os leads
                        $('.lead-checkbox').prop('checked', true);
                        selectedCount = $('.lead-checkbox').length;
                        break;
                        
                    case 'date':
                        // Selecionar por data
                        const dataInicio = $('input[name="data_inicio"]').val();
                        const dataFim = $('input[name="data_fim"]').val();
                        
                        if (!dataInicio || !dataFim) {
                            alert('Por favor, selecione um período válido');
                            return;
                        }
                        
                        $('.lead-checkbox').each(function() {
                            const dataEnvio = $(this).closest('tr').find('td:last').text();
                            if (dataEnvio >= dataInicio && dataEnvio <= dataFim) {
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
                
                // Atualizar contador de leads selecionados
                $('#selectedLeadsCount').text(selectedCount);
                
                // Fechar a modal e remover o backdrop
                $('#leadSelectionModal').on('hidden.bs.modal', function () {
    $('body').css('overflow', 'auto'); // Restaura o scroll
    $('body').css('padding-right', ''); // Remove o padding adicional
    $('.modal-backdrop').remove();
});
                
                // Adicionar mensagem de confirmação
                if (selectedCount > 0) {
                    $('<div>')
                        .addClass('alert alert-success mt-2')
                        .text(`${selectedCount} leads selecionados com sucesso!`)
                        .insertAfter('#selectedLeadsCount')
                        .fadeOut(3000);
                }
            });
            
            // Atualizar contagem quando checkboxes individuais são clicados
            $('.lead-checkbox').change(function() {
                const count = $('.lead-checkbox:checked').length;
                $('#selectedLeadsCount').text(count);
            });
            
            // Mostrar/esconder seção de datas
            $('input[name="selectionType"]').change(function() {
                if ($(this).val() === 'date') {
                    $('#dateRangeSection').removeClass('d-none');
                } else {
                    $('#dateRangeSection').addClass('d-none');
                }
            });
        });

        $('#massMessageForm').submit(function(e) {
            e.preventDefault();
            
            // Coletar todos os leads selecionados
            const selectedLeads = [];
            $('.lead-checkbox:checked').each(function() {
                selectedLeads.push($(this).val());
            });
            
            if (selectedLeads.length === 0) {
                alert('Por favor, selecione pelo menos um lead para envio.');
                return false;
            }

            // Adicionar os leads selecionados ao formulário
            selectedLeads.forEach(leadId => {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'selected_leads[]',
                    value: leadId
                }).appendTo($(this));
            });

            // Confirmar envio
            if (confirm(`Confirma o envio para ${selectedLeads.length} leads?`)) {
                this.submit();
            }
        });
    </script>

    <script>
        // Claude AI Integration - Frontend Code
        const PROXY_URL = 'claude_proxy.php';

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

        // AI Assistant Controls
        $(document).ready(function() {
            const $aiAssistant = $('#aiAssistant');
            const $aiThinking = $('.ai-thinking');
            const $aiResponse = $('#aiResponse');
            const $mensagem = $('#mensagem');
            const $aiActions = $('#aiActions');
            const $btnUsarSugestao = $('#btnUsarSugestao');

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

            function showSuccess(content, title = 'Sugestão') {
                if (!content) {
                    showError('Conteúdo da resposta vazio');
                    return;
                }

                const sanitizedContent = content
                    .replace(/</g, '<')
                    .replace(/>/g, '>')
                    .replace(/\n/g, '<br>');

                $aiResponse.html(`
                    <div class="alert alert-success">
                        <strong>${title}:</strong><br>
                        ${sanitizedContent}
                    </div>
                `);
                $aiActions.removeClass('d-none');
            }

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

            $('#btnCriarMensagem').click(async function() {
                const userPrompt = prompt('Sobre qual assunto você quer criar a mensagem?');
                if (!userPrompt || !userPrompt.trim()) return;

                const prompt = `
                    Crie uma mensagem persuasiva de WhatsApp sobre: "${userPrompt.trim()}"
                    
                    Requisitos:
                    - Tom profissional e amigável
                    - Inclua call-to-action claro
                    - Use emojis apropriados
                    - Máximo de 200 caracteres
                    - Estrutura: Saudação → Contexto → Benefício → Call-to-action
                    - Linguagem natural e envolvente
                    
                    Responda apenas com a mensagem, sem explicações adicionais.
                `.trim();

                await processAIRequest(prompt, 'mensagem');
            });

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
                    updateMessagePreview();
                    $aiAssistant.addClass('d-none');
                } else {
                    showError('Nenhum conteúdo disponível na sugestão');
                }
            });

            let previewTimeout;
            $mensagem.on('input', function() {
                clearTimeout(previewTimeout);
                previewTimeout = setTimeout(updateMessagePreview, 500);
            });

            function updateMessagePreview() {
                const messageText = $mensagem.val();
                if (!messageText) {
                    $('#messagePreview').html('Preview da mensagem...');
                    return;
                }

                const sanitizedText = messageText
                    .replace(/</g, '<')
                    .replace(/>/g, '>')
                    .replace(/\n/g, '<br>');

                $('#messagePreview').html(sanitizedText);
            }

            // Limpa timeouts pendentes ao desmontar
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

            // Tratamento de erros global
            window.onerror = function(msg, url, line, col, error) {
                console.error('Erro global:', {msg, url, line, col, error});
                showError('Erro inesperado. Por favor, tente novamente.');
                $aiThinking.addClass('d-none');
                return false;
            };
        });
    </script>
    <script>
        $(document).ready(function() {
            const leads = <?php echo json_encode($leads); ?>;
            let currentLeadIndex = 0;

            // Atualiza preview da mensagem
            $('#mensagem').on('input', updateMessagePreview);

            function updateMessagePreview() {
                let mensagem = $('#mensagem').val();
                if (leads.length > 0) {
                    mensagem = mensagem.replace('{nome}', leads[0].nome);
                }
                $('#messagePreview').html(mensagem.replace(/\n/g, '<br>'));
            }

            // Inicializa preview
            updateMessagePreview();

            $('#massMessageForm').on('submit', function(e) {
                e.preventDefault(); // Impede o envio padrão do formulário

                const confirmacao = confirm(`Você está prestes a enviar mensagens para ${leads.length} leads. Deseja continuar?`);
                if (confirmacao) {
                    iniciarEnvioEmMassa();
                }
            });

            function finalizarEnvio() {
                $('#btnEnviar').prop('disabled', false);
                mostrarNotificacao('Envio em massa concluído!\nTotal de mensagens enviadas: ' + processedLeads.size, 'success');

                // Atualiza a página para mostrar o status atualizado dos leads
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }

            let processedLeads = new Set(); // Conjunto para rastrear leads já processados

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

                $('#currentLead').text(currentLeadIndex + 1);
                const progress = ((currentLeadIndex + 1) / leads.length) * 100;
                $('.progress-bar').css('width', progress + '%');

                const deviceId = $('#dispositivo').val();

                if (!deviceId) {
                    mostrarNotificacao('Erro: Dispositivo não selecionado', 'error');
                    return;
                }

                // Formatar o número corretamente
                let numero = lead.numero.replace(/\D/g, '');
                if (numero.length === 10 || numero.length === 11) {
                    if (!numero.startsWith('55')) {
                        numero = '55' + numero;
                    }
                }

                // Obter o caminho do arquivo do campo oculto
                const filePath = $('#caminhoArquivo').val();

                const data = {
                    deviceId: deviceId,
                    number: numero,
                    message: $('#mensagem').val().replace('{nome}', lead.nome),
                    mediaPath: filePath // Adiciona o caminho do arquivo
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
                            $.post('registrar_envio.php', {
                                lead_id: lead.id,
                                dispositivo_id: deviceId,
                                status: 'ENVIADO',
                                arquivo: filePath // Salva o caminho do arquivo no banco
                            });

                            mostrarNotificacao('Mensagem enviada com sucesso para ' + lead.nome, 'success');
                        } else {
                            mostrarNotificacao('Erro ao enviar mensagem para ' + lead.nome + ': ' + response.message, 'error');
                        }

                        currentLeadIndex++;
                        setTimeout(enviarProximaMensagem, Math.random() * 5000 + 5000);
                    },
                    error: function(xhr, status, error) {
                        console.error('Erro na requisição:', xhr.responseText);
                        mostrarNotificacao('Erro ao enviar mensagem para ' + lead.nome + ': ' + error, 'error');

                        currentLeadIndex++;
                        setTimeout(enviarProximaMensagem, 5000);
                    }
                });
            }

            function iniciarEnvioEmMassa() {
                processedLeads.clear(); // Limpa o conjunto de leads processados
                currentLeadIndex = 0;

                // Verifica se há leads para enviar
                if (leads.length === 0) {
                    mostrarNotificacao('Não há leads para enviar mensagens.', 'error');
                    return;
                }

                // Verifica o dispositivo selecionado
                const deviceId = $('#dispositivo').val();
                if (!deviceId) {
                    mostrarNotificacao('Por favor, selecione um dispositivo.', 'error');
                    return;
                }

                $('#btnEnviar').prop('disabled', true);
                $('#progressBar, #sendingStatus').removeClass('d-none');
                $('#totalLeads').text(leads.length);

                enviarProximaMensagem();
            }

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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</body>
</html>