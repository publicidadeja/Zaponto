<?php

/**
 *  ZapLocal - Perfil do Usuário (perfil.php)
 *
 *  Este arquivo permite que o usuário visualize e edite suas informações de perfil,
 *  veja detalhes da sua assinatura e histórico de pagamentos, e cancele a assinatura.
 *
 *  Seções:
 *  - Inicialização e Inclusões
 *  - Recuperação de Dados (usuário, plano, pagamentos)
 *  - Processamento do Formulário de Atualização
 *  - HTML da Página (formulário, exibição de dados, modal de cancelamento)
 *  - JavaScript (preview da foto, inicialização do modal)
 */

//--------------------------------------------------
// Inicialização e Inclusões
//--------------------------------------------------

session_start();
require_once '../includes/auth.php';
redirecionarSeNaoLogado();
require_once '../includes/db.php';

$page_title = 'Meu Perfil';

//--------------------------------------------------
// Recuperação de Dados
//--------------------------------------------------

// Histórico de Pagamentos (últimos 5)
$stmt = $pdo->prepare("
    SELECT p.*, a.status as status_pagamento
    FROM pagamentos p
    LEFT JOIN assinaturas a ON p.assinatura_id = a.id
    WHERE p.usuario_id = ?
    ORDER BY p.data_pagamento DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['usuario_id']]);
$paymentHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dados do Usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

// Plano Atual do Usuário
$stmt = $pdo->prepare("SELECT u.plano_id, p.* FROM usuarios u
                       LEFT JOIN planos p ON u.plano_id = p.id
                       WHERE u.id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$currentPlan = $stmt->fetch(PDO::FETCH_ASSOC);


//--------------------------------------------------
// Processamento do Formulário de Atualização
//--------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $profilePicture = $userData['foto_perfil']; // Foto atual como padrão

        // Upload da Nova Foto (se houver)
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['foto_perfil'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png'];

            if (!in_array($extension, $allowedExtensions)) {
                throw new Exception("Tipo de arquivo inválido. Use JPG, JPEG ou PNG.");
            }

            $uploadDir = '../uploads/perfil/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);  // Cria diretório com permissões
            }

            $newName = uniqid('profile_') . '.' . $extension;
            $filePath = $uploadDir . $newName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Remove foto antiga (se existir)
                if (!empty($userData['foto_perfil']) && file_exists($userData['foto_perfil'])) {
                    unlink($userData['foto_perfil']);
                }
                $profilePicture = $filePath;
            }
        }

        // Atualização dos Dados do Perfil
        $stmt = $pdo->prepare("UPDATE usuarios SET
            nome = ?, email = ?, telefone = ?, empresa = ?, site = ?, foto_perfil = ?
            WHERE id = ?");

        $stmt->execute([
            $_POST['nome'],
            $_POST['email'],
            $_POST['telefone'],
            $_POST['empresa'],
            $_POST['site'],
            $profilePicture,
            $_SESSION['usuario_id']
        ]);

        // Atualização da Senha (se nova senha fornecida)
        if (!empty($_POST['nova_senha'])) {
            if (password_verify($_POST['senha_atual'], $userData['senha'])) {
                $newPasswordHash = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $stmt->execute([$newPasswordHash, $_SESSION['usuario_id']]);
                $_SESSION['mensagem'] = "Perfil e senha atualizados!";
            } else {
                $_SESSION['erro'] = "Senha atual incorreta.";
            }
        } else {
            $_SESSION['mensagem'] = "Perfil atualizado!";
        }

    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao atualizar perfil: " . $e->getMessage();
    } catch (Exception $e) {
        $_SESSION['erro'] = $e->getMessage(); //  Mensagem de erro mais amigável
    }

    header('Location: perfil.php'); // Redireciona para evitar reenvio do form
    exit;
}

//--------------------------------------------------
//  CSS (Estilos Específicos da Página)
//--------------------------------------------------

$extra_css = '
<style>
    /* Estilos gerais */
    .profile-container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
    .profile-section { background: #fff; border-radius: var(--border-radius); padding: 2rem; box-shadow: var(--card-shadow); margin-bottom: 2rem; }

    /* Cabeçalho do perfil */
    .profile-header { display: flex; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
    .profile-avatar { position: relative; width: 150px; height: 150px; border-radius: 50%; overflow: hidden; background: #f8f9fa; display: flex; align-items: center; justify-content: center; }
    .profile-avatar .profile-image { width: 100%; height: 100%; object-fit: cover; }
    .profile-avatar i { font-size: 4rem; color: #adb5bd; }
    .profile-info { flex: 1; }
    .profile-info h2 { margin: 0; color: var(--text-color); }
    .profile-info p { margin: 0.5rem 0 0; color: #8094ae; }

    /* Seções do formulário */
    .form-section { margin-top: 2rem; }
    .form-section h4 { color: var(--text-color); margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 0.5rem; }
    .form-section h4 i { color: var(--primary-color); }

    /* Plano atual */
    .current-plan { background: #f8f9fa; padding: 1.5rem; border-radius: var(--border-radius); border-left: 4px solid var(--primary-color); margin-bottom: 1rem; }
    .current-plan h5 { color: var(--primary-color); margin-bottom: 1rem; }
    .plan-features { margin: 1rem 0; }
    .plan-features li { margin-bottom: 0.5rem; color: #666; }
    .plan-features i { color: var(--primary-color); margin-right: 0.5rem; }

    /* Botões */
    .btn-primary { background-color: var(--primary-color); border-color: var(--primary-color); }
    .btn-primary:hover { background-color: var(--primary-hover); border-color: var(--primary-hover); }

    /* Responsividade */
    @media (max-width: 991px) { .col-lg-4 { margin-top: 2rem; } }
    @media (max-width: 768px) {
        .profile-header { justify-content: center; text-align: center; }
        .profile-info { width: 100%; text-align: center; }
    }

    .current-plan { background: #f8f9fa; padding: 1.5rem; border-radius: var(--border-radius); border-left: 4px solid var(--primary-color); margin-bottom: 1rem; }
    .current-plan h5 { color: var(--primary-color); margin-bottom: 1rem; }
    .badge-success { background-color: var(--primary-color); }
    .table-sm { font-size: 0.9rem; }
    .table-responsive { max-height: 300px; overflow-y: auto; }
    .table-sm { font-size: 0.85rem; }
    .badge { padding: 0.4em 0.6em; font-size: 0.75rem; }
    .btn-sm { margin-right: 0.5rem; }
    .current-plan .table-responsive { max-height: 200px; overflow-y: auto; }
    .current-plan .table td, .current-plan .table th { padding: 0.5rem; }
</style>';

//--------------------------------------------------
// Inclusão do Header (Cabeçalho)
//--------------------------------------------------
include '../includes/header.php';
?>

<!-- Container Principal -->
<div class="profile-container">
    <!-- Alertas -->
    <?php if (isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['mensagem'] ?>
            <?php unset($_SESSION['mensagem']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['erro'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['erro'] ?>
            <?php unset($_SESSION['erro']); ?>
        </div>
    <?php endif; ?>



    <div class="row">
        <!-- Coluna do Perfil (Esquerda) -->
        <div class="col-lg-8">
            <div class="profile-section">
                <form method="POST" enctype="multipart/form-data">
                    <!-- Cabeçalho do Perfil -->
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php if (!empty($userData['foto_perfil'])): ?>
                                <img src="<?= htmlspecialchars($userData['foto_perfil']) ?>" alt="Foto de perfil" class="profile-image">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="profile-info">
                            <h2><?= htmlspecialchars($userData['nome']) ?></h2>
                            <p><?= htmlspecialchars($userData['email']) ?></p>
                            <div class="mt-3">
                                <label for="foto_perfil" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-camera"></i> Alterar foto
                                </label>
                                <input type="file" id="foto_perfil" name="foto_perfil" class="d-none" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <!-- Informações Pessoais -->
                    <div class="form-section">
                        <h4><i class="fas fa-user-circle"></i> Informações Pessoais</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($userData['nome']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">E-mail</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($userData['email']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="tel" name="telefone" class="form-control" value="<?= htmlspecialchars($userData['telefone'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Informações Profissionais -->
                    <div class="form-section">
                        <h4><i class="fas fa-building"></i> Informações Profissionais</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Empresa</label>
                                <input type="text" name="empresa" class="form-control" value="<?= htmlspecialchars($userData['empresa'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Site</label>
                                <input type="url" name="site" class="form-control" value="<?= htmlspecialchars($userData['site'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Alterar Senha -->
                    <div class="form-section">
                        <h4><i class="fas fa-lock"></i> Alterar Senha</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Senha Atual</label>
                                <input type="password" name="senha_atual" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nova Senha</label>
                                <input type="password" name="nova_senha" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Confirmar Nova Senha</label>
                                <input type="password" name="confirmar_senha" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Botão de Salvar -->
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Coluna do Plano (Direita) -->
        <div class="col-lg-4">
            <div class="profile-section">
                <h4><i class="fas fa-box"></i> Detalhes da Assinatura</h4>

                <?php if ($currentPlan): ?>
                    <div class="current-plan">
                        <h5><?= htmlspecialchars($currentPlan['nome']) ?></h5>
                        <p class="text-muted">R$ <?= number_format($currentPlan['preco'], 2, ',', '.') ?>/mês</p>
                        <p><strong>Status:</strong> <span class="badge badge-success">Ativo</span></p>
                        <p><strong>Próximo Pagamento:</strong>
                            <?php
                            if (isset($userData['proximo_pagamento'])) {
                                echo date('d/m/Y', strtotime($userData['proximo_pagamento']));
                            } else {
                                echo "Não definido";
                            }
                            ?>
                        </p>

                        <!-- Histórico de Pagamentos -->
                        <div class="mt-3">
                            <h6>Últimos Pagamentos</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Valor</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paymentHistory as $payment): ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($payment['data_pagamento'])) ?></td>
                                                <td>R$ <?= number_format($payment['valor'], 2, ',', '.') ?></td>
                                                <td>
                                                    <?php if ($payment['status_pagamento'] === 'ativo'): ?>
                                                        <span class="badge badge-success">Pago</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Cancelado</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-3">
                            <a href="planos.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-sync-alt"></i> Atualizar Plano
                            </a>
                            <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#cancelarModal">
                                <i class="fas fa-times"></i> Cancelar Assinatura
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center">
                        <p>Você não possui uma assinatura ativa.</p>
                        <a href="planos.php" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i> Escolher um Plano
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal de Cancelamento -->
        <div class="modal fade" id="cancelarModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar Cancelamento</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Tem certeza que deseja cancelar sua assinatura?</p>
                        <p>Você perderá acesso aos recursos premium ao final do período atual.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Não</button>
                        <form action="cancelado.php" method="POST">
                            <input type="hidden" name="subscription_id" value="<?= isset($userData['stripe_subscription_id']) ? $userData['stripe_subscription_id'] : '' ?>">
                            <button type="submit" name="cancelar_assinatura" class="btn btn-danger">Sim, Cancelar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> <!-- Fim do row e do container principal -->

<!-- -------------------------------------------------- -->
<!-- JavaScript (Scripts)                                -->
<!-- -------------------------------------------------- -->
<script>
    // Preview da foto de perfil
    document.getElementById('foto_perfil').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const profileAvatar = document.querySelector('.profile-avatar');
                profileAvatar.innerHTML = `<img src="${e.target.result}" alt="Foto de perfil" class="profile-image">`;
            }
            reader.readAsDataURL(file);
        }
    });
</script>

<!-- jQuery, Bootstrap, e scripts personalizados -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/notifications.js"></script>

<!-- Inicialização do Modal (Bootstrap) -->
<script>
    $(document).ready(function() {
        $('#cancelarModal').modal({
            keyboard: true,
            backdrop: 'static'
        });
    });
</script>

<?php include '../includes/footer.php'; ?>