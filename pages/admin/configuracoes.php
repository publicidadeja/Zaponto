<?php
session_start();
include '../../includes/db.php';
include '../../includes/admin-auth.php';

// Verificar se é admin
redirecionarSeNaoAdmin();

// Processar formulário de configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Atualizar configurações gerais
        $stmt = $pdo->prepare("UPDATE configuracoes SET 
            nome_site = ?,
            email_suporte = ?,
            whatsapp_suporte = ?,
            tempo_entre_envios = ?,
            max_leads_dia = ?,
            max_mensagens_dia = ?,
            termos_uso = ?,
            politica_privacidade = ?
            WHERE id = 1");
            
        $stmt->execute([
            $_POST['nome_site'],
            $_POST['email_suporte'],
            $_POST['whatsapp_suporte'],
            $_POST['tempo_entre_envios'],
            $_POST['max_leads_dia'],
            $_POST['max_mensagens_dia'],
            $_POST['termos_uso'],
            $_POST['politica_privacidade']
        ]);

        $_SESSION['mensagem'] = "Configurações atualizadas com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao atualizar configurações: " . $e->getMessage();
    }
    
    header('Location: configuracoes.php');
    exit;
}

// Buscar configurações atuais
$config = $pdo->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Painel Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3547DB;
            --primary-hover: #283593;
            --success-color: #2CC149;
            --background-color: #f7f9fc;
            --text-color: #364a63;
            --border-color: #e2e8f0;
            --card-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.05);
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

        .config-section {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
        }

        .config-section h4 {
            color: var(--text-color);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
        }

        .config-section h4 i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .form-control {
            border-color: var(--border-color);
            padding: 0.6rem 1rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(53, 71, 219, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.6rem 1.5rem;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-color: var(--border-color);
        }

        .note-editor {
            border-color: var(--border-color) !important;
        }

        .note-toolbar {
            background-color: #f8f9fa !important;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Configurações do Sistema</h2>
                </div>

                <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php 
                        echo $_SESSION['mensagem'];
                        unset($_SESSION['mensagem']);
                        ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['erro'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php 
                        echo $_SESSION['erro'];
                        unset($_SESSION['erro']);
                        ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <form method="POST" id="configForm">
                    <!-- Configurações Gerais -->
                    <div class="config-section">
                        <h4>
                            <i class="fas fa-cogs"></i>
                            Configurações Gerais
                        </h4>
                        <div class="form-group">
                            <label>Nome do Site</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-globe"></i>
                                    </span>
                                </div>
                                <input type="text" name="nome_site" class="form-control" value="<?php echo htmlspecialchars($config['nome_site']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email de Suporte</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                </div>
                                <input type="email" name="email_suporte" class="form-control" value="<?php echo htmlspecialchars($config['email_suporte']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>WhatsApp de Suporte</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fab fa-whatsapp"></i>
                                    </span>
                                </div>
                                <input type="text" name="whatsapp_suporte" id="whatsapp_suporte" class="form-control" value="<?php echo htmlspecialchars($config['whatsapp_suporte']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Limites e Restrições -->
                    <div class="config-section">
                        <h4>
                            <i class="fas fa-shield-alt"></i>
                            Limites e Restrições
                        </h4>
                        <div class="form-group">
                            <label>Tempo Entre Envios (segundos)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-clock"></i>
                                    </span>
                                </div>
                                <input type="number" name="tempo_entre_envios" class="form-control" value="<?php echo htmlspecialchars($config['tempo_entre_envios']); ?>" required min="0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Máximo de Leads por Dia</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-users"></i>
                                    </span>
                                </div>
                                <input type="number" name="max_leads_dia" class="form-control" value="<?php echo htmlspecialchars($config['max_leads_dia']); ?>" required min="0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Máximo de Mensagens por Dia</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                </div>
                                <input type="number" name="max_mensagens_dia" class="form-control" value="<?php echo htmlspecialchars($config['max_mensagens_dia']); ?>" required min="0">
                            </div>
                        </div>
                    </div>

                    <!-- Termos e Políticas -->
                    <div class="config-section">
                        <h4>
                            <i class="fas fa-file-contract"></i>
                            Termos e Políticas
                        </h4>
                        <div class="form-group">
                            <label>Termos de Uso</label>
                            <textarea name="termos_uso" id="termos_uso" class="form-control"><?php echo htmlspecialchars($config['termos_uso']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Política de Privacidade</label>
                            <textarea name="politica_privacidade" id="politica_privacidade" class="form-control"><?php echo htmlspecialchars($config['politica_privacidade']); ?></textarea>
                        </div>
                    </div>

                    <div class="text-right mb-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save mr-2"></i>
                            Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar o editor de texto rico para os campos de termos e políticas
            $('#termos_uso, #politica_privacidade').summernote({
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                lang: 'pt-BR',
                callbacks: {
                    onImageUpload: function(files) {
                        alert('Upload de imagens não é permitido diretamente. Por favor, use URLs de imagens.');
                    }
                }
            });

            // Máscara para o campo de WhatsApp
            $('#whatsapp_suporte').mask('(00) 00000-0000');

            // Fechar alertas automaticamente após 5 segundos
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);

            // Confirmação antes de enviar o formulário
            $('#configForm').on('submit', function(e) {
                if (!confirm('Tem certeza que deseja salvar as alterações?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>