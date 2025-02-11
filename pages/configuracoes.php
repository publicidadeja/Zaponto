<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/db.php';

// Consulta para obter os dados atuais do usuário
$stmt = $pdo->prepare("SELECT token_dispositivo, mensagem_base, arquivo_padrao FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

// Variável para mensagens de status
$mensagem_status = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = trim($_POST['token_dispositivo']);
    $mensagem_base = mb_convert_encoding(trim($_POST['mensagem_base']), 'UTF-8', 'auto');

    // Processa o upload do arquivo
    if ($_FILES['arquivo_padrao']['error'] == UPLOAD_ERR_OK) {
        $arquivo_tmp = $_FILES['arquivo_padrao']['tmp_name'];
        $arquivo_nome = $_FILES['arquivo_padrao']['name'];
        $arquivo_destino = '../uploads/' . $arquivo_nome;

        if (move_uploaded_file($arquivo_tmp, $arquivo_destino)) {
            $stmt = $pdo->prepare("UPDATE usuarios SET token_dispositivo = ?, mensagem_base = ?, arquivo_padrao = ? WHERE id = ?");
            if ($stmt->execute([$token, $mensagem_base, $arquivo_nome, $_SESSION['usuario_id']])) {
                $mensagem_status = "<div class='alert alert-success'>Configurações atualizadas com sucesso!</div>";
            } else {
                $mensagem_status = "<div class='alert alert-danger'>Erro ao atualizar as configurações.</div>";
            }
        } else {
            $mensagem_status = "<div class='alert alert-danger'>Erro ao salvar o arquivo.</div>";
        }
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET token_dispositivo = ?, mensagem_base = ? WHERE id = ?");
        if ($stmt->execute([$token, $mensagem_base, $_SESSION['usuario_id']])) {
            $mensagem_status = "<div class='alert alert-success'>Configurações atualizadas com sucesso!</div>";
        } else {
            $mensagem_status = "<div class='alert alert-danger'>Erro ao atualizar as configurações.</div>";
        }
    }

    // Recarrega os dados do usuário após a atualização
    $stmt = $pdo->prepare("SELECT token_dispositivo, mensagem_base, arquivo_padrao FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();
}

// Definir o título da página
$page_title = 'Configurações';

// CSS específico para esta página
$extra_css = '
<style>
    .config-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 0 1rem;
    }

    .config-card {
        background: #fff;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        padding: 2rem;
    }

    .config-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }

    .config-title {
        color: var(--text-color);
        font-size: 1.75rem;
        margin: 0;
    }

    .config-subtitle {
        color: var(--text-secondary);
        margin-top: 0.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--text-color);
    }

    .form-control {
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        padding: 0.75rem;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(53, 71, 219, 0.15);
    }

    .btn-submit {
        background-color: var(--primary-color);
        color: #fff;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: var(--border-radius);
        font-weight: 600;
        transition: all 0.2s ease;
        width: 100%;
    }

    .btn-submit:hover {
        background-color: var(--primary-hover);
        transform: translateY(-1px);
    }

    .file-info {
        margin-top: 0.5rem;
        padding: 0.5rem;
        background-color: var(--background-color);
        border-radius: var(--border-radius);
        font-size: 0.875rem;
    }

    @media (max-width: 768px) {
        .config-card {
            padding: 1.5rem;
        }

        .config-title {
            font-size: 1.5rem;
        }
    }
</style>';

// Incluir o header
include '../includes/header.php';
?>

<div class="config-container">
    <div class="config-card">
        <div class="config-header">
            <h1 class="config-title">
                <i class="fas fa-cog me-2"></i>Configurações do Sistema
            </h1>
            <p class="config-subtitle">Personalize suas configurações de envio</p>
        </div>

        <?php if ($mensagem_status): ?>
            <?php echo $mensagem_status; ?>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" accept-charset="utf-8">
            <div class="form-group">
                <label for="mensagem_base" class="form-label">Mensagem Base</label>
                <textarea 
                    class="form-control" 
                    id="mensagem_base" 
                    name="mensagem_base" 
                    rows="4" 
                    placeholder="Digite sua mensagem base aqui..."
                ><?php echo htmlspecialchars($usuario['mensagem_base'] ?? ''); ?></textarea>
                <small class="text-muted">Use {nome} para personalizar a mensagem com o nome do destinatário.</small>
            </div>

            <div class="form-group">
                <label for="arquivo_padrao" class="form-label">Arquivo Padrão</label>
                <input 
                    type="file" 
                    class="form-control" 
                    id="arquivo_padrao" 
                    name="arquivo_padrao"
                >
                <?php if (!empty($usuario['arquivo_padrao'])): ?>
                    <div class="file-info">
                        <i class="fas fa-file me-2"></i>
                        Arquivo atual: <?php echo htmlspecialchars($usuario['arquivo_padrao']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save me-2"></i>Salvar Configurações
            </button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>