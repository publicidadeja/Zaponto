<?php
session_start();
include '../../includes/db.php';
include '../../includes/admin-auth.php';

// Verificar se é admin
redirecionarSeNaoAdmin();

// Processar exclusão de usuário
if (isset($_POST['excluir_usuario'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$_POST['usuario_id']]);
        $_SESSION['mensagem'] = "Usuário excluído com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao excluir usuário: " . $e->getMessage();
    }
    header('Location: usuarios.php');
    exit;
}

// Processar adição/edição de usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $plano_id = $_POST['plano_id'];
    $status = $_POST['status'];

    try {
        if ($_POST['acao'] === 'adicionar') {
            $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, telefone, plano_id, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $senha, $telefone, $plano_id, $status]);
            $_SESSION['mensagem'] = "Usuário adicionado com sucesso!";
        } else if ($_POST['acao'] === 'editar') {
            try {
                // Iniciar transação
                $pdo->beginTransaction();
                
                $usuario_id = $_POST['usuario_id'];
                
                // Atualizar usuário
                if (!empty($_POST['senha'])) {
                    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ?, telefone = ?, plano_id = ?, status = ? WHERE id = ?");
                    $stmt->execute([$nome, $email, $senha, $telefone, $plano_id, $status, $usuario_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, plano_id = ?, status = ? WHERE id = ?");
                    $stmt->execute([$nome, $email, $telefone, $plano_id, $status, $usuario_id]);
                }
                
                // Buscar detalhes do novo plano
                $stmt = $pdo->prepare("SELECT * FROM planos WHERE id = ?");
                $stmt->execute([$plano_id]);
                $novo_plano = $stmt->fetch();
                
                // Desativar assinaturas ativas existentes
                $stmt = $pdo->prepare("
                    UPDATE assinaturas 
                    SET status = 'inativo' 
                    WHERE usuario_id = ? 
                    AND status = 'ativo'
                ");
                $stmt->execute([$usuario_id]);
                
                // Inserir nova assinatura
                $stmt = $pdo->prepare("
                    INSERT INTO assinaturas (
                        usuario_id, 
                        plano_id, 
                        status,
                        data_inicio,
                        proximo_pagamento
                    ) VALUES (?, ?, 'ativo', NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH))
                ");
                $stmt->execute([
                    $usuario_id,
                    $plano_id
                ]);
                
                $pdo->commit();
                $_SESSION['mensagem'] = "Usuário atualizado com sucesso!";
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['erro'] = "Erro ao atualizar usuário: " . $e->getMessage();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao processar usuário: " . $e->getMessage();
    }
    header('Location: usuarios.php');
    exit;
}

function atualizarAssinaturaUsuario($pdo, $usuario_id, $novo_plano_id) {
    try {
        $pdo->beginTransaction();
        
        // Deactivate all current subscriptions
        $stmt = $pdo->prepare("
            UPDATE assinaturas 
            SET status = 'inativo' 
            WHERE usuario_id = ? 
            AND status = 'ativo'
        ");
        $stmt->execute([$usuario_id]);
        
        // Create new subscription
        $stmt = $pdo->prepare("
            INSERT INTO assinaturas (
                usuario_id, 
                plano_id, 
                status,
                data_inicio,
                proximo_pagamento
            ) VALUES (?, ?, 'ativo', NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH))
        ");
        $stmt->execute([$usuario_id, $novo_plano_id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

// Buscar planos para o select
$planos = $pdo->query("SELECT * FROM planos ORDER BY nome")->fetchAll();

// Listar usuários com informações do plano
$usuarios = $pdo->query("
    SELECT u.*, p.nome as plano_nome 
    FROM usuarios u 
    LEFT JOIN planos p ON u.plano_id = p.id 
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Painel Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.22/css/dataTables.bootstrap4.min.css">
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

        .card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            padding: 1rem;
            background-color: #f8f9fa;
            color: var(--text-color);
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
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

        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
        }

        .badge-success {
            background-color: var(--success-color);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
            color: #fff !important;
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
                    <h2>Gerenciar Usuários</h2>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#modalUsuario" onclick="limparFormulario()">
                        <i class="fas fa-plus mr-2"></i>Adicionar Usuário
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

                <!-- Card com a Tabela -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tabelaUsuarios" class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuário</th>
                                        <th>Email</th>
                                        <th>Telefone</th>
                                        <th>Plano</th>
                                        <th>Status</th>
                                        <th>Data Cadastro</th>
                                        <th width="100">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><?php echo $usuario['id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar">
                                                    <?php echo strtoupper(substr($usuario['nome'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <?php echo htmlspecialchars($usuario['nome']); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['telefone']); ?></td>
                                        <td>
                                            <span class="badge badge-light">
                                                <?php echo htmlspecialchars($usuario['plano_nome']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $usuario['status'] === 'ativo' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($usuario['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-info" onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($usuario)); ?>)" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?php echo $usuario['id']; ?>)" title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Usuário -->
    <div class="modal fade" id="modalUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Adicionar Usuário</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="formUsuario" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="acao" id="acao" value="adicionar">
                        <input type="hidden" name="usuario_id" id="usuario_id">
                        
                        <div class="form-group">
                            <label>Nome</label>
                            <input type="text" name="nome" id="nome" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Senha</label>
                            <input type="password" name="senha" id="senha" class="form-control">
                            <small class="form-text text-muted">Deixe em branco para manter a senha atual (ao editar)</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Telefone</label>
                            <input type="text" name="telefone" id="telefone" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Plano</label>
                            <select name="plano_id" id="plano_id" class="form-control" required>
                                <?php foreach ($planos as $plano): ?>
                                    <option value="<?php echo $plano['id']; ?>">
                                        <?php echo htmlspecialchars($plano['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
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

    <!-- Modal Confirmação de Exclusão -->
    <div class="modal fade" id="modalConfirmacao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir este usuário?</p>
                    <p class="text-danger"><small>Esta ação não poderá ser desfeita.</small></p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="usuario_id" id="excluir_usuario_id">
                        <input type="hidden" name="excluir_usuario" value="1">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar DataTable com configurações melhoradas
            $('#tabelaUsuarios').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.22/i18n/Portuguese-Brasil.json"
                },
                "pageLength": 10,
                "order": [[0, "desc"]],
                "responsive": true,
                "dom": '<"top"f>rt<"bottom"lip><"clear">',
                "drawCallback": function() {
                    $('.dataTables_paginate > .pagination').addClass('pagination-sm');
                }
            });

            // Adicionar máscara ao telefone
            $('#telefone').mask('(00) 00000-0000');

            // Adicionar tooltips
            $('[title]').tooltip();

            // Fechar alertas automaticamente após 5 segundos
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });

        function limparFormulario() {
            $('#formUsuario')[0].reset();
            $('#acao').val('adicionar');
            $('#usuario_id').val('');
            $('#modalTitle').text('Adicionar Usuário');
            $('#senha').prop('required', true);
        }

        function editarUsuario(usuario) {
            $('#acao').val('editar');
            $('#usuario_id').val(usuario.id);
            $('#nome').val(usuario.nome);
            $('#email').val(usuario.email);
            $('#telefone').val(usuario.telefone);
            $('#plano_id').val(usuario.plano_id);
            $('#status').val(usuario.status);
            $('#senha').prop('required', false);
            $('#modalTitle').text('Editar Usuário');
            $('#modalUsuario').modal('show');
        }

        function confirmarExclusao(usuarioId) {
            $('#excluir_usuario_id').val(usuarioId);
            $('#modalConfirmacao').modal('show');
        }
    </script>
</body>
</html>