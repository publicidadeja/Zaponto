<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/db.php';
include '../includes/functions.php';

//após a verificação de sessão
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
if (!$verificacao['is_ilimitado'] && $verificacao['restantes'] <= ($verificacao['limite_total'] * 0.2)) {
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

// Consulta para obter os dispositivos CONECTADOS do usuário e dados do usuário
$stmt = $pdo->prepare("SELECT d.*, u.mensagem_base, u.arquivo_padrao FROM dispositivos d 
                       JOIN usuarios u ON u.id = d.usuario_id 
                       WHERE d.usuario_id = ? AND d.status = 'CONNECTED' 
                       ORDER BY d.created_at DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$dispositivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para obter a mensagem base e arquivo padrão do usuário
$stmt = $pdo->prepare("SELECT mensagem_base, arquivo_padrao FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();
$mensagem_base = $usuario['mensagem_base'];
$arquivo_padrao = $usuario['arquivo_padrao'];

// Verificar se existem dispositivos conectados
if (empty($dispositivos)) {
    $mensagem_status = [
        'tipo' => 'warning', 
        'texto' => 'Você precisa ter pelo menos um dispositivo conectado para enviar mensagens. <a href="dispositivos.php">Conectar dispositivo</a>'
    ];
}

// Variável para controlar a exibição da mensagem de sucesso/erro
$mensagem_status = null;


/**
 * Valida um número de telefone no formato brasileiro para WhatsApp.
 *
 * @param string $numero Número de telefone a ser validado.
 * @return bool True se o número for válido, False caso contrário.
 */
function validarNumeroWhatsAppBrasil($numero) {
    // Remove caracteres não numéricos
    $numero = preg_replace('/[^0-9]/', '', $numero);

    // Verifica se tem 10 ou 11 dígitos (sem código do país)
    if (strlen($numero) < 10 || strlen($numero) > 11) {
        return false;
    }

    // Lista de DDDs válidos (você pode expandir essa lista se necessário)
    $dddsValidos = [
        11, 12, 13, 14, 15, 16, 17, 18, 19,
        21, 22, 24, 27, 28,
        31, 32, 33, 34, 35, 37, 38,
        41, 42, 43, 44, 45, 46, 47, 48, 49,
        51, 53, 54, 55,
        61, 62, 63, 64, 65, 66, 67, 68, 69,
        71, 73, 74, 75, 77, 79,
        81, 82, 83, 84, 85, 86, 87, 88, 89,
        91, 92, 93, 94, 95, 96, 97, 98, 99
    ];

    // Extrai o DDD
    $ddd = substr($numero, 0, 2);

    // Verifica se o DDD é válido
    if (!in_array($ddd, $dddsValidos)) {
        return false;
    }

    // Se tiver 11 dígitos, verifica o nono dígito (deve ser 9 para celular)
    if (strlen($numero) == 11) {
        $nonoDigito = substr($numero, 2, 1);
        if ($nonoDigito != '9') {
            return false;
        }
    }

    return true;
}


/**
 * Verifica se um número já existe na base de dados.
 *
 * @param PDO $pdo Conexão com o banco de dados.
 * @param string $numero Número a ser verificado.
 * @param int $usuario_id ID do usuário atual.
 * @return array|false Retorna um array com os dados do lead se existir, false caso contrário.
 */
function verificarDuplicidade(PDO $pdo, $numero, $usuario_id) {
    $stmt = $pdo->prepare("SELECT nome FROM leads_enviados WHERE numero = ? AND usuario_id = ?");
    $stmt->execute([$numero, $usuario_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $numero_raw = $_POST['numero'];
    $numero = preg_replace('/[^0-9]/', '', $numero_raw);
    $nome = preg_replace('/[^a-zA-ZÀ-ÿ\s]/', '', trim($_POST['nome']));
    $dispositivo_id = $_POST['dispositivo_id'] ?? '';

    //--- VALIDAÇÕES ---
    if (empty($nome)) {
        $mensagem_status = ['tipo' => 'danger', 'texto' => 'O campo nome não pode estar vazio'];
    } elseif (empty($dispositivo_id)) {
        $mensagem_status = ['tipo' => 'danger', 'texto' => 'Selecione um dispositivo'];
    } else {
        // 1. VERIFICAÇÃO DE DUPLICIDADE (opcional - comente se não quiser)
        $duplicado = verificarDuplicidade($pdo, $numero, $_SESSION['usuario_id']);
        if ($duplicado) {
            $mensagem_status = [
                'tipo' => 'warning',
                'texto' => "Este número já pertence a {$duplicado['nome']} em sua base de leads!  Envie a mensagem mesmo assim no botão abaixo, se desejar."
            ];
            // Se encontrou duplicado, NÃO continua a validação.  O usuário decide se envia.
        } else {
            // 2. VALIDAÇÃO DO NÚMERO
            if (!validarNumeroWhatsAppBrasil($numero)) {
                $mensagem_status = ['tipo' => 'danger', 'texto' => 'Número de telefone inválido para WhatsApp no Brasil. Verifique o DDD e o nono dígito (se aplicável).'];
            } else {
                //---  NÚMERO VÁLIDO, CONTINUA O PROCESSO ---
                try {
                    // Verificar status do dispositivo
                    $stmt = $pdo->prepare("SELECT status FROM dispositivos WHERE device_id = ? AND usuario_id = ?");
                    $stmt->execute([$dispositivo_id, $_SESSION['usuario_id']]);
                    $device = $stmt->fetch();

                    if (!$device || $device['status'] !== 'CONNECTED') {
                        throw new Exception('Dispositivo não está conectado. Por favor, reconecte o dispositivo.');
                    }

                    // Formatar número para o padrão WhatsApp (SEMPRE com 55)
                    $numero = '55' . $numero;


                    // Personalizar mensagem
                    $mensagem_personalizada = str_replace('{nome}', $nome, $mensagem_base);

                    // Preparar dados para envio
                    $data = [
                        'deviceId' => $dispositivo_id,
                        'number' => $numero,
                        'message' => $mensagem_personalizada
                    ];

                    // Adicionar arquivo padrão se existir
                    if (!empty($arquivo_padrao)) {
                        $arquivo_path = '../uploads/' . $arquivo_padrao;
                        if (file_exists($arquivo_path)) {
                            $data['mediaPath'] = $arquivo_path;
                        }
                    }

                    // Log para debug
                    error_log('Enviando mensagem: ' . json_encode($data));

                    // Enviar mensagem via API
                    $ch = curl_init('http://localhost:3000/send-message');
                    curl_setopt_array($ch, [
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => json_encode($data),
                        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 30
                    ]);

                    $response = curl_exec($ch);
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                    // Log para debug
                    error_log('Resposta API: ' . $response);
                    error_log('HTTP Code: ' . $http_code);

                    if ($response === false) {
                        throw new Exception('Erro na conexão com o servidor: ' . curl_error($ch));
                    }

                    curl_close($ch);
                    $result = json_decode($response, true);

                    if ($http_code == 200 && isset($result['success']) && $result['success']) {
                        // Inserir na tabela de leads enviados
                        $stmt = $pdo->prepare("INSERT INTO leads_enviados (usuario_id, dispositivo_id, numero, mensagem, nome, status, arquivo) 
                                             VALUES (?, ?, ?, ?, ?, 'ENVIADO', ?)");
                        $stmt->execute([
                            $_SESSION['usuario_id'],
                            $dispositivo_id,
                            $numero,
                            $mensagem_personalizada,
                            $nome,
                            $arquivo_padrao
                        ]);

                        $mensagem_status = ['tipo' => 'success', 'texto' => 'Mensagem enviada com sucesso!'];
                    } else {
                        $error_message = isset($result['message']) ? $result['message'] : 'Erro desconhecido';
                        throw new Exception('Falha ao enviar mensagem: ' . $error_message);
                    }
                } catch (Exception $e) {
                    $mensagem_status = ['tipo' => 'danger', 'texto' => $e->getMessage()];
                    error_log('Erro ao enviar mensagem: ' . $e->getMessage());
                }
            } // Fim do else (validação do número)
        } // Fim do else (verificação de duplicidade)
    } //Fim do else principal
} // Fim do if ($_SERVER['REQUEST_METHOD'] == 'POST')
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Mensagem - Zaponto</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS (Material Design) -->
    <style>
        /* Cores ZapLocal */
        :root {
            --primary-color: #0098fc; /* Azul institucional */
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Header */
        .navbar {
            background-color: #fff;
            box-shadow: var(--card-shadow);
            padding: 1rem 1.5rem;
        }

        .navbar-brand img {
            height: 40px; /* Ajuste a altura do logo */
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
            max-width: 1200px;
            padding: 2rem;
        }

        /* Form Container */
        .form-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--text-color);
        }

        .form-container .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .form-container .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .form-container .form-label {
            color: var(--text-color);
            font-weight: 600;
        }

        .form-container .form-control {
            border-radius: 8px;
            border-color: var(--border-color);
        }

        .form-container .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .form-container .input-group-text {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px 0 0 8px;
        }

        /* Mensagem de Status */
        .mensagem-status {
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            padding: 1rem;
        }

        .mensagem-status.success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .mensagem-status.danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .mensagem-status.warning {
            background-color: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-0.25rem);
        }

        .card-body {
            padding: 2rem;
        }

        .card-body.bg-light {
            background-color: #f8f9fa !important;
        }

        /* Footer */
        .dashboard-footer {
            text-align: center;
            padding: 1.75rem 0;
            margin-top: auto;
            border-top: 1px solid var(--border-color);
            color: #6c757d;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .form-container {
                padding: 1.5rem;
            }
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

        .text-success {
    color: var(--success-color) !important; /* Use a cor de sucesso do seu tema */
}

.text-danger {
    color: var(--danger-color) !important; /* Use a cor de erro do seu tema */
}
/* Ajuste para o conteúdo principal ocupar toda a largura em telas menores */
        @media (max-width: 768px) {
            .col-md-9 {
                width: 100%;
            }
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
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
    <div class="row align-items-center">
        <!-- Coluna da Imagem -->
        <div class="col-lg-6 mb-4 mb-lg-0 d-none d-lg-block">
            <img src="https://publicidadeja.com.br/wp-content/uploads/2025/02/ENVIAR-MENSAGEM-ZAPONTO.png" 
                 alt="Ilustração Enviar Mensagem" 
                 class="img-fluid">
        </div>
        
        <!-- Coluna do Formulário -->
        <div class="col-lg-6">
                <div class="form-container">
                    <h2><i class="fas fa-paper-plane me-2"></i> Enviar Mensagem</h2>

                    <!-- Mensagem de Status -->
                    <?php if ($mensagem_status): ?>
                        <div class="mensagem-status <?php echo htmlspecialchars($mensagem_status['tipo']); ?>">
                            <?php echo htmlspecialchars($mensagem_status['texto']); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <!-- Campo de Número -->
                        <div class="mb-3">
                            <label for="numero" class="form-label">Número de Telefone:</label>
                            <div class="input-group">
                                <span class="input-group-text">+55</span>
                                <input type="text" name="numero" id="numero" class="form-control" placeholder="Digite o número (com DDD)" required>
                            </div>
                        </div>

                        <!-- Campo de Nome -->
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome:</label>
                            <input type="text" name="nome" id="nome" class="form-control" placeholder="Digite o nome" required>
                        </div>

                       <!-- Seleção de Dispositivo -->
                        <div class="mb-3">
                            <label for="dispositivo_id" class="form-label">Dispositivo:</label>
                            <select name="dispositivo_id" id="dispositivo_id" class="form-control" required>
                                <option value="">Selecione um dispositivo...</option>
                                <?php
                                $dispositivo_selecionado = false; // Variável para controlar a seleção

                                foreach ($dispositivos as $dispositivo):
                                    $is_connected = ($dispositivo['status'] === 'CONNECTED');
                                    $selected = (!$dispositivo_selecionado && $is_connected) ? 'selected' : ''; // Seleciona o primeiro conectado
                                    if ($is_connected && !$dispositivo_selecionado) {
                                        $dispositivo_selecionado = true; // Marca que um dispositivo foi selecionado
                                    }
                                    ?>
                                    <option value="<?php echo htmlspecialchars($dispositivo['device_id']); ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($dispositivo['nome']); ?>
                                        (Status:
                                        <?php if ($is_connected): ?>
                                            <span class="text-success"><i class="fas fa-circle"></i> Conectado</span>
                                        <?php else: ?>
                                            <span class="text-danger"><i class="fas fa-circle"></i> Desconectado</span>
                                        <?php endif; ?>
                                        )
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Visualização da Mensagem Base -->
                        <div class="mb-3">
                            <label class="form-label">Mensagem que será enviada:</label>
                            <div class="card">
                                <div class="card-body bg-light">
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($mensagem_base)); ?></p>
                                </div>
                            </div>
                            <small class="form-text text-muted">A mensagem será personalizada com o nome inserido acima.</small>
                        </div>

                        <!-- Visualização do Arquivo Padrão -->
                        <div class="mb-3">
                            <label class="form-label">Arquivo que será enviado:</label>
                            <?php if (!empty($arquivo_padrao)): ?>
                                <div class="card">
                                    <div class="card-body bg-light">
                                        <i class="fas fa-file me-2"></i>
                                        <?php echo htmlspecialchars($arquivo_padrao); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Nenhum arquivo padrão configurado</p>
                            <?php endif; ?>
                        </div>

                        <!-- Botão de Envio -->
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane me-2"></i> Enviar Mensagem
                        </button>
                         <!-- Botão de Envio (Duplicado) -->
                        <?php if (isset($duplicado) && $duplicado): ?>
                            <button type="submit" name="enviar_duplicado" class="btn btn-warning w-100 mt-2">
                                <i class="fas fa-exclamation-triangle me-2"></i> Enviar Mesmo Assim
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <!-- Máscara para o campo de número -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#numero').inputmask('(99) 99999-9999'); // Máscara para números brasileiros
        });
    </script>
    
    <script>
    $(document).ready(function() {
        let isSubmitting = false;

        $('form').on('submit', function(e) {
            // Verifica se o botão "Enviar Mesmo Assim" foi clicado
            if (e.originalEvent && e.originalEvent.submitter && e.originalEvent.submitter.name === 'enviar_duplicado') {
                // Permite o envio normal, sem validações adicionais
                isSubmitting = true;
                return; // Não previne o comportamento padrão
            }

            e.preventDefault(); // Previne o envio padrão do formulário

            if (isSubmitting) return; // Evita múltiplos envios

            var $form = $(this);
            var numero = $('#numero').val().replace(/\D/g, '');

            // 1. VERIFICAÇÃO DE DUPLICIDADE (agora feita via AJAX)
            $.ajax({
                url: 'verificar_numero.php', // Script PHP que faz a verificação
                method: 'POST',
                data: { numero: numero },
                dataType: 'json', // Espera uma resposta JSON
                success: function(data) {
                    if (data.existe) {
                        // Número duplicado:  Mostra o aviso e o botão "Enviar Mesmo Assim"
                        $('.alert').remove(); // Remove alertas anteriores
                        $form.find('button[type="submit"]').after(
                            '<button type="submit" name="enviar_duplicado" class="btn btn-warning w-100 mt-2">' +
                            '<i class="fas fa-exclamation-triangle me-2"></i> Enviar Mesmo Assim' +
                            '</button>'
                        );
                         $form.prepend(
                                    '<div class="alert alert-warning">' +
                                    'Este número já pertence a ' + data.nome + ' em sua base de leads!' +
                                    '</div>'
                                );
                        // Não envia o formulário neste caso.
                    } else {
                        // Número não duplicado:  Prossegue com o envio normal
                        isSubmitting = true;
                        $('.alert').remove(); // Remove alertas
                        $form.off('submit').submit(); // Remove o manipulador de eventos atual e envia
                    }
                },
                error: function() {
                    // Erro na requisição AJAX:  Loga o erro e permite o envio (melhor do que travar)
                    console.error("Erro na verificação de duplicidade.");
                    isSubmitting = true;
                    $form.off('submit').submit();
                }
            });
        });
    });
</script>

</body>
</html>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/notifications.js"></script>