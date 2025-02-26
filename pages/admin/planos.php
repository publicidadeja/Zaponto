<?php
session_start();
include '../../includes/db.php';
include '../../includes/admin-auth.php';
include '../../includes/stripe-config.php';

// Verificar se é admin
redirecionarSeNaoAdmin();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nome = $_POST['nome'];
                $preco = $_POST['preco'];
                $descricao = $_POST['descricao'];
                $recursos = isset($_POST['recursos']) ? json_encode($_POST['recursos']) : '[]';
                $limite_leads = isset($_POST['leads_ilimitado']) ? -1 : $_POST['limite_leads'];
                $limite_mensagens = isset($_POST['mensagens_ilimitado']) ? -1 : $_POST['limite_mensagens'];
                $stripe_price_id = $_POST['stripe_price_id'];
                $tem_ia = isset($_POST['tem_ia']) ? 1 : 0;

                // Desativa qualquer período de teste ativo
    $stmt = $pdo->prepare("
    UPDATE assinaturas 
    SET status = 'inativo' 
    WHERE usuario_id = ? 
    AND is_trial = 1 
    AND status = 'ativo'
");
$stmt->execute([$usuario_id]);

// Insere a nova assinatura
$stmt = $pdo->prepare("INSERT INTO planos (...) VALUES (...)");
                
                $stmt = $pdo->prepare("INSERT INTO planos (nome, preco, descricao, recursos, limite_leads, limite_mensagens, stripe_price_id, tem_ia, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$nome, $preco, $descricao, $recursos, $limite_leads, $limite_mensagens, $stripe_price_id, $tem_ia]);
                $_SESSION['mensagem'] = "Plano adicionado com sucesso!";
                break;

            case 'edit':
                $id = $_POST['id'];
                $nome = $_POST['nome'];
                $preco = $_POST['preco'];
                $descricao = $_POST['descricao'];
                $recursos = isset($_POST['recursos']) ? json_encode($_POST['recursos']) : '[]';
                $limite_leads = isset($_POST['leads_ilimitado']) ? -1 : $_POST['limite_leads'];
                $limite_mensagens = isset($_POST['mensagens_ilimitado']) ? -1 : $_POST['limite_mensagens'];
                $stripe_price_id = $_POST['stripe_price_id'];
                $tem_ia = isset($_POST['tem_ia']) ? 1 : 0;
                
                $stmt = $pdo->prepare("UPDATE planos SET nome = ?, preco = ?, descricao = ?, recursos = ?, limite_leads = ?, limite_mensagens = ?, stripe_price_id = ?, tem_ia = ? WHERE id = ?");
                $stmt->execute([$nome, $preco, $descricao, $recursos, $limite_leads, $limite_mensagens, $stripe_price_id, $tem_ia, $id]);
                $_SESSION['mensagem'] = "Plano atualizado com sucesso!";
                break;

            case 'delete':
                $id = $_POST['id'];
                $usuarios = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE plano_id = ?");
                $usuarios->execute([$id]);
                if ($usuarios->fetchColumn() > 0) {
                    $_SESSION['erro'] = "Não é possível excluir este plano pois existem usuários vinculados a ele.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM planos WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['mensagem'] = "Plano excluído com sucesso!";
                }
                break;
        }
        header('Location: planos.php');
        exit;
    }
}

// Buscar planos
$planos = $pdo->query("SELECT * FROM planos ORDER BY preco ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Planos - Painel Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
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

        .plan-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
            transition: transform 0.2s;
            box-shadow: var(--card-shadow);
        }

        .plan-card:hover {
            transform: translateY(-5px);
        }

        .plan-card .card-body {
            padding: 1.5rem;
        }

        .plan-card .card-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .plan-card .list-unstyled li {
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .plan-card .list-unstyled li:last-child {
            border-bottom: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .modal-content {
            border-radius: 10px;
            box-shadow: var(--card-shadow);
        }

        .modal-header {
            background-color: var(--background-color);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
        }

        .form-control {
            border-color: var(--border-color);
            padding: 0.6rem 1rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(53, 71, 219, 0.25);
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .btn-group {
            gap: 0.5rem;
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff;
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
                    <h2>Gerenciar Planos</h2>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addPlanModal">
                        <i class="fas fa-plus mr-2"></i>Adicionar Plano
                    </button>
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

                <!-- Lista de Planos -->
                <div class="row">
                    <?php foreach ($planos as $plano): ?>
                        <div class="col-md-4">
                            <div class="card plan-card">
                                <div class="card-body">
                                    <h5 class="card-title d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($plano['nome']); ?>
                                        <span class="badge badge-primary">
                                            R$ <?php echo number_format($plano['preco'], 2, ',', '.'); ?>
                                        </span>
                                    </h5>
                                    <p class="card-text text-muted">
                                        <?php echo htmlspecialchars($plano['descricao']); ?>
                                    </p>
                                    <ul class="list-unstyled mt-3">
                                        <li>
                                            <i class="fas fa-users mr-2 text-primary"></i>
                                            Leads: <?php echo $plano['limite_leads'] == -1 ? 'Ilimitado' : number_format($plano['limite_leads']); ?>
                                        </li>
                                        <li>
                                            <i class="fas fa-envelope mr-2 text-primary"></i>
                                            Mensagens: <?php echo $plano['limite_mensagens'] == -1 ? 'Ilimitado' : number_format($plano['limite_mensagens']); ?>
                                        </li>
                                        <li>
                                            <i class="fab fa-stripe mr-2 text-primary"></i>
                                            ID: <?php echo $plano['stripe_price_id']; ?>
                                        </li>
                                        <li>
                                            <i class="fas fa-robot mr-2 text-primary"></i>
                                            IA: <?php echo $plano['tem_ia'] ? 'Sim' : 'Não'; ?>
                                        </li>
                                    </ul>
                                    <div class="btn-group w-100 mt-3">
                                        <button class="btn btn-outline-primary" onclick="editPlan(<?php echo htmlspecialchars(json_encode($plano)); ?>)">
                                            <i class="fas fa-edit mr-2"></i>Editar
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deletePlan(<?php echo $plano['id']; ?>)">
                                            <i class="fas fa-trash mr-2"></i>Excluir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar Plano -->
    <div class="modal fade" id="addPlanModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Plano</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label>Nome do Plano</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Preço (R$)</label>
                            <input type="number" name="preco" class="form-control" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Descrição</label>
                            <textarea name="descricao" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>ID do Produto Stripe</label>
                            <input type="text" name="stripe_price_id" class="form-control" required>
                            <small class="form-text text-muted">Insira o ID do produto criado no Stripe (ex: price_H5ggYwtDq4fbrJ)</small>
                        </div>
                        <div class="form-group">
                            <label>Limite de Leads</label>
                            <div class="input-group">
                                <input type="number" name="limite_leads" class="form-control" id="add-limite-leads">
                                <div class="input-group-append">
                                    <div class="input-group-text">
                                        <input type="checkbox" name="leads_ilimitado" id="add-leads-ilimitado" onchange="toggleLimiteLeads('add')">
                                        <label class="mb-0 ml-2">Ilimitado</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Limite de Mensagens</label>
                            <div class="input-group">
                                <input type="number" name="limite_mensagens" class="form-control" id="add-limite-mensagens">
                                <div class="input-group-append">
                                    <div class="input-group-text">
                                        <input type="checkbox" name="mensagens_ilimitado" id="add-mensagens-ilimitado" onchange="toggleLimiteMensagens('add')">
                                        <label class="mb-0 ml-2">Ilimitado</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="tem_ia" value="1">
                                Incluir Assistente de IA
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Plano -->
    <div class="modal fade" id="editPlanModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Plano</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="form-group">
                            <label>Nome do Plano</label>
                            <input type="text" name="nome" id="edit-nome" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Preço (R$)</label>
                            <input type="number" name="preco" id="edit-preco" class="form-control" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Descrição</label>
                            <textarea name="descricao" id="edit-descricao" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>ID do Produto Stripe</label>
                            <input type="text" name="stripe_price_id" id="edit-stripe-price-id" class="form-control" required>
                            <small class="form-text text-muted">Insira o ID do produto criado no Stripe (ex: price_H5ggYwtDq4fbrJ)</small>
                        </div>
                        <div class="form-group">
                            <label>Limite de Leads</label>
                            <div class="input-group">
                                <input type="number" name="limite_leads" class="form-control" id="edit-limite-leads">
                                <div class="input-group-append">
                                    <div class="input-group-text">
                                        <input type="checkbox" name="leads_ilimitado" id="edit-leads-ilimitado" onchange="toggleLimiteLeads('edit')">
                                        <label class="mb-0 ml-2">Ilimitado</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Limite de Mensagens</label>
                            <div class="input-group">
                                <input type="number" name="limite_mensagens" class="form-control" id="edit-limite-mensagens">
                                <div class="input-group-append">
                                    <div class="input-group-text">
                                        <input type="checkbox" name="mensagens_ilimitado" id="edit-mensagens-ilimitado" onchange="toggleLimiteMensagens('edit')">
                                        <label class="mb-0 ml-2">Ilimitado</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="tem_ia" id="edit-tem-ia" value="1">
                                Incluir Assistente de IA
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Form para exclusão -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete-id">
    </form>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        function toggleLimiteLeads(prefix) {
            const checkbox = document.getElementById(`${prefix}-leads-ilimitado`);
            const input = document.getElementById(`${prefix}-limite-leads`);
            
            if (checkbox.checked) {
                input.value = '';
                input.disabled = true;
            } else {
                input.disabled = false;
            }
        }

        function toggleLimiteMensagens(prefix) {
            const checkbox = document.getElementById(`${prefix}-mensagens-ilimitado`);
            const input = document.getElementById(`${prefix}-limite-mensagens`);
            
            if (checkbox.checked) {
                input.value = '';
                input.disabled = true;
            } else {
                input.disabled = false;
            }
        }

        function editPlan(plano) {
            document.getElementById('edit-id').value = plano.id;
            document.getElementById('edit-nome').value = plano.nome;
            document.getElementById('edit-preco').value = plano.preco;
            document.getElementById('edit-descricao').value = plano.descricao;
            document.getElementById('edit-stripe-price-id').value = plano.stripe_price_id;
            
            const leadsIlimitado = plano.limite_leads === -1;
            document.getElementById('edit-leads-ilimitado').checked = leadsIlimitado;
            document.getElementById('edit-limite-leads').value = leadsIlimitado ? '' : plano.limite_leads;
            document.getElementById('edit-limite-leads').disabled = leadsIlimitado;
            
            const mensagensIlimitado = plano.limite_mensagens === -1;
            document.getElementById('edit-mensagens-ilimitado').checked = mensagensIlimitado;
            document.getElementById('edit-limite-mensagens').value = mensagensIlimitado ? '' : plano.limite_mensagens;
            document.getElementById('edit-limite-mensagens').disabled = mensagensIlimitado;
            
            document.getElementById('edit-tem-ia').checked = plano.tem_ia == 1;
            $('#editPlanModal').modal('show');
        }

        function deletePlan(id) {
            if (confirm('Tem certeza que deseja excluir este plano?')) {
                document.getElementById('delete-id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Inicializar tooltips
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>