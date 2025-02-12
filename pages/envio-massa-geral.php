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

// Preparar dados para envio
$data = [
    'deviceId' => $dispositivo_id,
    'number' => formatarNumeroWhatsApp($lead['numero']),
    'message' => $mensagem_personalizada
];

// Adicionar arquivo apenas se existir um upload válido
if (!empty($arquivo_path) && file_exists($arquivo_path)) {
    $data['mediaPath'] = $arquivo_path;
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
                if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === 0) {
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
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0098fc;
            --primary-hover: #283593;
            --success-color: #2CC149;
            --background-color: #f7f9fc;
            --text-color: #364a63;
            --border-color: #e2e8f0;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            font-family: 'Nunito', sans-serif;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .progress {
            height: 20px;
            margin-top: 1rem;
        }

        #selectedLeadsCount {
            font-weight: bold;
            color: var(--primary-color);
        }

        .modal-header {
            background-color: var(--primary-color);
            color: white;
        }

        .lead-selection-options {
            margin-bottom: 1rem;
            padding: 1rem;
            background-color: #fff;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-paper-plane me-2"></i>Envio em Massa</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['mensagem'])): ?>
                            <div class="alert alert-success">
                                <?php 
                                echo $_SESSION['mensagem'];
                                unset($_SESSION['mensagem']);
                                ?>
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

                            <!-- Message Input -->
                            <div class="mb-3">
                                <label class="form-label">Mensagem</label>
                                <textarea name="mensagem" class="form-control" rows="4" required><?php echo htmlspecialchars($mensagem_base); ?></textarea>
                                <div class="form-text">Use {nome} para incluir o nome do lead na mensagem.</div>
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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const leadsTable = $('#leadsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
                }
            });

            // Garantir limpeza adequada quando o modal for fechado
$('#leadSelectionModal').on('hidden.bs.modal', function () {
    $('body').removeClass('modal-open');
    $('.modal-backdrop').remove();
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
$('#leadSelectionModal').modal('hide');
$('body').removeClass('modal-open');
$('.modal-backdrop').remove();
            
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
</body>
</html>