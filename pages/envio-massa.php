<?php

/**
 *  ZapLocal - Envio em Massa (envio-massa.php) - Parte 1
 *
 *  Este arquivo permite aos usuários enviar mensagens em massa para leads via WhatsApp,
 *  integrando-se com uma API externa (Claude) para sugestões de mensagens.
 *
 *  Seções (Parte 1):
 *  - Inicialização e Inclusões
 *  - Funções Auxiliares
 *  - Recuperação de Dados
 *  - Processamento do Formulário (Parte 1: Preparação)
 *  - HTML (Início)
 */

//--------------------------------------------------
// Inicialização e Inclusões
//--------------------------------------------------

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/db.php';
require_once '../includes/functions.php';

// verificação de limites
$verificacao = verificarLimitesEnvio($pdo, $_SESSION['usuario_id']);

if (!$verificacao['pode_enviar']) {
    // Se não puder enviar, mostra mensagem de erro
    $mensagem = [
        'tipo' => 'error',
        'texto' => sprintf(
            'Você atingiu o limite de envios do seu plano (%d mensagens). 
             Entre em contato com o suporte para aumentar seu limite.',
            $verificacao['limite_total']
        )
    ];
    
    // Se for uma requisição AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo json_encode(['status' => 'error', 'message' => $mensagem['texto']]);
        exit;
    }
    
    // Se for uma requisição normal
    $_SESSION['mensagem'] = $mensagem;
    header('Location: dashboard.php');
    exit;
}

// Se ainda tiver envios disponíveis mas estiver próximo do limite (80%)
if ($verificacao['restantes'] <= ($verificacao['limite_total'] * 0.2)) {
    $mensagem = [
        'tipo' => 'warning',
        'texto' => sprintf(
            'Atenção: Você tem apenas %d envios restantes de um total de %d.',
            $verificacao['restantes'],
            $verificacao['limite_total']
        )
    ];
    
    $_SESSION['mensagem'] = $mensagem;
}

//--------------------------------------------------
// Funções Auxiliares
//--------------------------------------------------

/**
 * Busca dispositivos conectados do usuário.
 */
function buscarDispositivosConectados(PDO $pdo, int $usuario_id): array
{
    $stmt = $pdo->prepare("SELECT d.*, u.mensagem_base FROM dispositivos d
                           JOIN usuarios u ON u.id = d.usuario_id
                           WHERE d.usuario_id = ? AND d.status = 'CONNECTED'
                           ORDER BY d.created_at DESC");
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Busca a mensagem base do usuário.
 */
function buscarMensagemBaseUsuario(PDO $pdo, int $usuario_id): string
{
    $stmt = $pdo->prepare("SELECT mensagem_base FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);  // Use FETCH_ASSOC
    return $usuario['mensagem_base'] ?? '';
}

/**
 * Busca leads do usuário.
 */
function buscarLeadsUsuario(PDO $pdo, int $usuario_id): array
{
    $stmt = $pdo->prepare("SELECT l.*, d.nome as dispositivo_nome
              FROM leads_enviados l
              LEFT JOIN dispositivos d ON l.dispositivo_id = d.device_id
              WHERE l.usuario_id = ?");
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Processa o upload de arquivos.
 *
 * @return string Caminho do arquivo ou string vazia se não houver upload.
 * @throws Exception Se houver erro no upload.
 */
function processarUploadArquivo(): string
{
    $filePath = '';
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        $fileName = uniqid('file_') . '_' . time() . '_' . $_FILES['arquivo']['name'];
        $filePath = $uploadDir . $fileName;

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $filePath)) {
            throw new Exception("Erro ao fazer upload do arquivo.");
        }
    }
    return $filePath;
}

/**
 * Valida os dados do formulário de envio.
 */
function validarDadosEnvio(?string $dispositivo_id, ?string $mensagem, ?array $selected_leads): array
{
    $errors = [];
    if (empty($dispositivo_id)) {
        $errors[] = "Selecione um dispositivo.";
    }
    if (empty($mensagem)) {
        $errors[] = "A mensagem não pode estar vazia.";
    }
    if (empty($selected_leads)) {
        $errors[] = "Selecione pelo menos um lead.";
    }
    return $errors;
}

/**
 * Cria a fila de mensagens no banco de dados.
 */
function criarFilaMensagens(PDO $pdo, int $usuario_id, string $dispositivo_id, string $mensagem, string $arquivo_path, array $selected_leads): void
{
    foreach ($selected_leads as $lead_id) {
        $stmt = $pdo->prepare("SELECT numero, nome FROM leads_enviados WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$lead_id, $usuario_id]);
        $lead = $stmt->fetch(PDO::FETCH_ASSOC);

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

/**
 * Inicia o processamento assíncrono da fila (via cURL).
 *
 * @throws Exception Se houver erro na requisição cURL.
 */
function iniciarProcessamentoAssincrono(int $usuario_id, string $dispositivo_id): void
{
    $ch = curl_init('http://localhost:3000/process-queue'); //  URL correta
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'usuario_id' => $usuario_id,
            'dispositivo_id' => $dispositivo_id
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 1,        // Timeout curto (operação assíncrona)
        CURLOPT_NOSIGNAL => 1         // Evita timeouts longos do sinal
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode != 200) {
        throw new Exception('Erro ao iniciar o processamento da fila');
    }
}

//--------------------------------------------------
// Recuperação de Dados
//--------------------------------------------------

$dispositivos = buscarDispositivosConectados($pdo, $_SESSION['usuario_id']);
$mensagem_base = buscarMensagemBaseUsuario($pdo, $_SESSION['usuario_id']);
$leads = buscarLeadsUsuario($pdo, $_SESSION['usuario_id']);
$sendErrors = []; // Inicializa a variável

//--------------------------------------------------
// Processamento do Formulário (Parte 1: Preparação)
//--------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $filePath = '';

    try {
        // Verificar limites de envio considerando quantidade de leads
        $verificacao = verificarLimitesEnvio($pdo, $_SESSION['usuario_id']);
        $dispositivo_id = $_POST['dispositivo_id'] ?? null;
        $mensagem = $_POST['mensagem'] ?? null;
        $selected_leads = $_POST['selected_leads'] ?? [];
        
        // Verificar quantidade de leads vs limites disponíveis
        $total_leads = count($selected_leads);
        if ($total_leads > $verificacao['restantes']) {
            throw new Exception(sprintf(
                'Você não tem mensagens suficientes disponíveis. Necessário: %d, Disponível: %d',
                $total_leads,
                $verificacao['restantes']
            ));
        }

        // Validar dados do envio
        $sendErrors = validarDadosEnvio($dispositivo_id, $mensagem, $selected_leads);

        if (empty($sendErrors)) {
            // Processar upload de arquivo se houver
            $filePath = processarUploadArquivo();
            
            // Criar fila de mensagens
            criarFilaMensagens($pdo, $_SESSION['usuario_id'], $dispositivo_id, $mensagem, $filePath, $selected_leads);
            
            // Iniciar processamento assíncrono
            iniciarProcessamentoAssincrono($_SESSION['usuario_id'], $dispositivo_id);

            $_SESSION['mensagem'] = "Envio iniciado! As mensagens serão enviadas em segundo plano.";
        }
    } catch (Exception $e) {
        error_log("Erro ao criar fila de envio: " . $e->getMessage());
        $sendErrors[] = $e->getMessage();
        
        // Se for erro de limite, adiciona mensagem específica
        if (strpos($e->getMessage(), 'mensagens suficientes') !== false) {
            $_SESSION['mensagem'] = [
                'tipo' => 'error',
                'texto' => $e->getMessage()
            ];
        } else {
            $_SESSION['mensagem'] = [
                'tipo' => 'error',
                'texto' => "Erro ao processar o envio. Por favor, tente novamente."
            ];
        }
    }

    // Se houver erros de validação
    if (!empty($sendErrors)) {
        $_SESSION['erros_envio'] = $sendErrors;
    }

    // Se for requisição AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        $response = [
            'status' => empty($sendErrors) ? 'success' : 'error',
            'message' => empty($sendErrors) ? 
                "Envio iniciado com sucesso!" : 
                implode(", ", $sendErrors)
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Se for requisição normal, redireciona
    if (empty($sendErrors)) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
    } else {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?error=1');
    }
    exit;
}

//--------------------------------------------------
// HTML (Início)
//--------------------------------------------------
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

/* Estilos para a imagem */
.img-fluid {
    max-width: 100%;
    height: auto;
    transition: transform 0.3s ease;
}

.img-fluid:hover {
    transform: scale(1.02);
}

/* Ajustes responsivos */
@media (max-width: 991.98px) {
    .form-container {
        max-width: 100%;
    }
}

@media (min-width: 992px) {
    .container {
        max-width: 1400px;
    }
}

/* Estilos para o alerta de envio concluído */
.alert-envio {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 20px 30px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 12px;
    border-left: 4px solid #2CC149;
    animation: fadeIn 0.3s ease-in-out;
}

.alert-envio i {
    font-size: 24px;
    color: #2CC149;
}

.alert-envio-content {
    color: #333;
    font-weight: 500;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translate(-50%, -40%);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%);
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
        transform: translate(-50%, -50%);
    }
    to {
        opacity: 0;
        transform: translate(-50%, -40%);
    }
}

.alert-envio.fadeOut {
    animation: fadeOut 0.3s ease-in-out forwards;
}
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
    <div class="row align-items-center">
        <!-- Coluna da Imagem -->
        <div class="col-lg-6 mb-4 mb-lg-0 d-none d-lg-block">
            <img src="https://publicidadeja.com.br/wp-content/uploads/2025/02/ENVIO-EM-MASSA-ZAPONTO.png" 
                 alt="Ilustração Enviar Mensagem" 
                 class="img-fluid">
        </div>
        
        <!-- Coluna do Formulário -->
        <div class="col-lg-6">
                <div class="form-container">
                    <h2 class="form-title"><i class="fas fa-paper-plane me-2"></i>Envio em Massa</h2>

                    <!-- Exibição de mensagens e erros -->
                    <?php if (isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-<?php echo $_SESSION['mensagem']['tipo'] == 'error' ? 'danger' : $_SESSION['mensagem']['tipo']; ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['mensagem']['texto']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['mensagem']); ?>
<?php endif; ?>

                    <?php if (!empty($sendErrors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($sendErrors as $error): ?>
                                    <li><?= $error ?></li>
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
                                    <option value="<?= htmlspecialchars($dispositivo['device_id']) ?>">
                                        <?= htmlspecialchars($dispositivo['nome']) ?>
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
                        <button type="button" class="btn btn-primary" id="btnIniciarEnvio">
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
                                                    <input type="checkbox" name="selected_leads[]" value="<?= $lead['id'] ?>" class="lead-checkbox">
                                                </td>
                                                <td><?= htmlspecialchars($lead['nome']) ?></td>
                                                <td><?= htmlspecialchars($lead['numero']) ?></td>
                                                <td><?= htmlspecialchars($lead['status']) ?></td>
                                                <td><?= formatarData($lead['data_envio']) ?></td>
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
                </div> <!-- Fim .form-container -->
            </div> <!-- Fim .col-md-12 -->
        </div> <!-- Fim .row -->
    </div> <!-- Fim .container -->

    <!-- Modal de Confirmação de Envio (Estilizado) -->
    <div class="modal fade" id="confirmacaoEnvioModal" tabindex="-1" aria-labelledby="confirmacaoEnvioModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmacaoEnvioModalLabel"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Confirmação de Envio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Você está prestes a enviar mensagens para <span id="numLeadsConfirmacao"></span> leads.</p>
                    <p><strong>Importante:</strong></p>
                    <ul>
                        <li>Certifique-se de que os leads selecionados deram consentimento para receber mensagens.</li>
                        <li>O envio em massa pode levar algum tempo, dependendo do número de leads.</li>
                        <li>Você pode continuar trabalhando com o Zaponto em outras telas normalmente durante o envio.</li>
                    </ul>
                    <p>Deseja prosseguir com o envio?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmarEnvioBtn">
                        <i class="fas fa-check me-2"></i>Confirmar Envio
                    </button>
                </div>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>

<!-- Scripts (Parte 1) -->
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


        // Adiciona mensagem de confirmação (opcional)
        if (selectedCount > 0) {
            $('<div>')
                .addClass('alert alert-success mt-2')
                .text(`${selectedCount} leads selecionados com sucesso!`)
                .insertAfter('#selectedLeadsCount')
                .fadeOut(3000);
        }
    });

    // Garante que o estado do body seja restaurado quando o modal for fechado
    $('#leadSelectionModal').on('hidden.bs.modal', function() {
        $('body').removeClass('modal-open');      // Remove a classe modal-open
        $('body').css('padding-right', '');     // Remove o padding-right
        $('.modal-backdrop').remove();          // Remove o backdrop
        $('body').css('overflow', 'auto'); // Restaura o overflow
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


// --- Início:  Código para UMA confirmação estilizada ---
$(document).ready(function() {
    $('#btnIniciarEnvio').click(function(e) {
        e.preventDefault();

        const selectedLeadsCount = $('.lead-checkbox:checked').length;
        if (selectedLeadsCount === 0) {
            alert('Por favor, selecione pelo menos um lead para envio.');
            return;
        }

        // Define o número de leads no modal de confirmação
        $('#numLeadsConfirmacao').text(selectedLeadsCount);

        // Mostra o modal de confirmação estilizado
        $('#confirmacaoEnvioModal').modal('show');
    });

    // Manipula o clique no botão "Confirmar Envio" dentro do modal
    $('#confirmarEnvioBtn').click(function() {
        // Fecha o modal de confirmação
        $('#confirmacaoEnvioModal').modal('hide');

        // Prepara os dados do formulário e inicia o envio via AJAX
        const formData = new FormData($('#massMessageForm')[0]);

        // Adiciona os leads selecionados ao FormData
        $('.lead-checkbox:checked').each(function() {
            formData.append('selected_leads[]', $(this).val());
        });

        $.ajax({
            url: $('#massMessageForm').attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Inicia o monitoramento do progresso
                iniciarMonitoramentoProgresso();
            },
            error: function(xhr, status, error) {
                alert('Erro ao iniciar o envio: ' + error);
            }
        });
    });

     // Remove o manipulador de submit original (agora desnecessário)
     $('#massMessageForm').off('submit');
});
// --- Fim: Código para UMA confirmação estilizada ---
</script>

<script>
// Integração com a API do Claude (Frontend)
const PROXY_URL = 'claude_proxy.php'; //  Arquivo proxy

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
            updateMessagePreview(); // Atualiza o preview
            $aiAssistant.addClass('d-none');
        } else {
            showError('Nenhum conteúdo disponível na sugestão');
        }
    });

    // Atualiza o preview da mensagem em tempo real (com debounce)
    let previewTimeout;
    $mensagem.on('input', function() {
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(updateMessagePreview, 500); // 500ms debounce
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

    // Tratamento de erros global (opcional)
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
    let processedLeads = new Set(); // Conjunto para rastrear leads processados

    // Atualiza o preview da mensagem
    $('#mensagem').on('input', updateMessagePreview);

    function updateMessagePreview() {
        let mensagem = $('#mensagem').val();
        if (leads.length > 0) {
            mensagem = mensagem.replace('{nome}', leads[0].nome); // Usa o primeiro lead
        }
        $('#messagePreview').html(mensagem.replace(/\n/g, '<br>'));
    }

    // Inicializa o preview
    updateMessagePreview();



    // Função para finalizar o envio
    function finalizarEnvio() {
        $('#btnEnviar').prop('disabled', false); // Reabilita o botão
        mostrarNotificacao('Envio em massa concluído!\nTotal de mensagens enviadas: ' + processedLeads.size, 'success');

        // Atualiza a página para mostrar o status dos leads
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
        $('#progressBar').css('width', progress + '%').attr('aria-valuenow', progress); // Atualiza barra


        const dispositivo_id = $('#dispositivo_id').val(); // ID correto

        if (!dispositivo_id) {
            mostrarNotificacao('Erro: Dispositivo não selecionado', 'error');
            return;
        }

        // Formata o número
        let numero = lead.numero.replace(/\D/g, '');
        if ((numero.length === 10 || numero.length === 11) && !numero.startsWith('55')) {
            numero = '55' + numero;
        }

        // Obtém o caminho do arquivo do campo oculto
        const filePath = $('input[name="arquivo"]').val(); // Valor do campo


        const data = {
            deviceId: dispositivo_id,
            number: numero,
            message: $('#mensagem').val().replace('{nome}', lead.nome),
            mediaPath: filePath // Adiciona o caminho
        };

        $.ajax({
            url: 'http://localhost:3000/send-message',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    processedLeads.add(lead.id); // Marca como processado

                    // Registra o envio no banco
                    $.post('registrar_envio.php', { // Arquivo que registra
                        lead_id: lead.id,
                        dispositivo_id: dispositivo_id,
                        status: 'ENVIADO',
                        arquivo: filePath // Salva o caminho
                    });

                    mostrarNotificacao('Mensagem enviada com sucesso para ' + lead.nome, 'success');
                } else {
                    mostrarNotificacao('Erro ao enviar mensagem para ' + lead.nome + ': ' + response.message, 'error');
                }

                currentLeadIndex++;
                setTimeout(enviarProximaMensagem, Math.random() * 5000 + 5000); // Intervalo
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição:', xhr.responseText);
                mostrarNotificacao('Erro ao enviar mensagem para ' + lead.nome + ': ' + error, 'error');

                currentLeadIndex++;
                setTimeout(enviarProximaMensagem, 5000); // Tenta novamente
            }
        });
    }


    // Função para iniciar o envio em massa
    function iniciarEnvioEmMassa() {
        processedLeads.clear(); // Limpa leads processados
        currentLeadIndex = 0;

        // Verifica se há leads
        if (leads.length === 0) {
            mostrarNotificacao('Não há leads para enviar mensagens.', 'error');
            return;
        }

        // Verifica o dispositivo
        const dispositivo_id = $('#dispositivo_id').val(); // ID correto
        if (!dispositivo_id) {
            mostrarNotificacao('Por favor, selecione um dispositivo.', 'error');
            return;
        }

        $('#btnEnviar').prop('disabled', true); // Desabilita o botão
        $('#progressSection, #sendingStatus').removeClass('d-none'); // Mostra progresso
        $('#totalCount').text(leads.length); // Total de leads

        enviarProximaMensagem();
    }

    // Funções de validação (opcional)
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

        // Remove após 3 segundos
        setTimeout(function() {
            $notificacao.removeClass('show');
            setTimeout(function() {
                $notificacao.remove();
            }, 300); // Aguarda transição
        }, 3000);
    }
});
</script>

<script>
// Função para iniciar o progresso
function iniciarProgresso(total) {
    // Mostra o container
    $('#progressContainer').removeClass('d-none');

    // Define o total
    $('#totalMessages').text(total);
    $('#currentMessage').text('0');

    // Reseta a barra
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



function iniciarMonitoramentoProgresso() {
    // Mostra o container
    $('#progressContainer').removeClass('d-none');

    // Inicia o monitoramento
    verificarProgressoFila();
    const progressInterval = setInterval(verificarProgressoFila, 2000); // Verifica a cada 2s

    function verificarProgressoFila() {
        fetch('check_queue_status.php') //  Arquivo que verifica
            .then(response => response.json())
            .then(data => {
                // Atualiza a barra
                const progressBar = document.getElementById('progressBar');
                const progressText = document.getElementById('progressText');
                const currentMessage = document.getElementById('currentMessage');
                const totalMessages = document.getElementById('totalMessages');

                progressBar.style.width = data.progress + '%';
                progressText.textContent = data.progress + '%';
                currentMessage.textContent = data.sent;
                totalMessages.textContent = (data.sent + data.pending + data.failed);

                if (data.status === 'completed') {
    clearInterval(progressInterval);
    setTimeout(() => {
        // Criar elemento de alerta
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert-envio';
        alertDiv.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <div class="alert-envio-content">Envio concluído!</div>
        `;
        
        // Adicionar ao corpo do documento
        document.body.appendChild(alertDiv);
        
        // Remover após 3 segundos com animação
        setTimeout(() => {
            alertDiv.classList.add('fadeOut');
            setTimeout(() => {
                alertDiv.remove();
            }, 300);
        }, 3000);
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