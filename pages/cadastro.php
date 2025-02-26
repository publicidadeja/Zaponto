<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $telefone = trim($_POST['telefone']);
    
    // Configurações do plano teste
    $plano_teste = [
        'limite_leads' => 100,
        'limite_mensagens' => 100,
        'tem_ia' => 1,
        'dias_teste' => 7
    ];

    // Validações básicas
    if (empty($nome) || empty($email) || empty($senha) || empty($telefone)) {
        $erro_cadastro = "Todos os campos são obrigatórios.";
    } elseif ($senha !== $confirmar_senha) {
        $erro_cadastro = "As senhas não coincidem.";
    } else {
        // Verifica se o email já está cadastrado
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $erro_cadastro = "Este email já está cadastrado.";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Cria o hash da senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                
                // Data atual e data de término do teste
                $data_atual = new DateTime();
                $data_fim_teste = (new DateTime())->modify('+7 days');
            
                // Insere o novo usuário
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, telefone, status, created_at) VALUES (?, ?, ?, ?, 'ativo', NOW())");

                if (!$stmt->execute([$nome, $email, $senha_hash, $telefone])) {
                    throw new Exception("Erro ao inserir usuário: " . print_r($stmt->errorInfo(), true));
                }
                $usuario_id = $pdo->lastInsertId();
            
                // Insere o período de teste
                $stmt = $pdo->prepare("INSERT INTO assinaturas (usuario_id, plano_id, status, data_inicio, data_fim, is_trial, limite_leads, limite_mensagens, tem_ia) VALUES (?, 4, 'ativo', ?, ?, 1, ?, ?, ?)");
                if (!$stmt->execute([
                    $usuario_id,
                    $data_atual->format('Y-m-d H:i:s'),
                    $data_fim_teste->format('Y-m-d H:i:s'),
                    $plano_teste['limite_leads'],
                    $plano_teste['limite_mensagens'],
                    $plano_teste['tem_ia']
                ])) {
                    throw new Exception("Erro ao inserir assinatura: " . print_r($stmt->errorInfo(), true));
                }
            
                $pdo->commit();
                $sucesso_cadastro = "Cadastro realizado com sucesso! <a href='login.php'>Clique aqui para fazer login</a>.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $erro_cadastro = "Erro ao cadastrar usuário: " . $e->getMessage();
                // Para debug:
                error_log("Erro no cadastro: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - ZapLocal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
            background-color: #f4f4f4;
        }

        .login-container {
            display: flex;
            height: 100vh;
        }

        .login-form {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            background: #fff;
        }

        .login-image {
            flex: 1;
            background: url('https://publicidadeja.com.br/wp-content/uploads/2025/02/TELA-DE-LOGIN-ZAPONTO-1.png') center/contain no-repeat;
            background-color: #F1FAFF;
        }

        .logo {
            max-width: 200px;
            margin-bottom: 2rem;
        }

        .form-box {
            width: 100%;
            max-width: 400px;
        }

        .form-control {
            height: 50px;
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 0.8rem;
            margin-bottom: 1rem;
            font-size: 16px;
        }

        .form-control:focus {
            border-color: #0098FC;
            box-shadow: 0 0 0 0.2rem rgba(0, 152, 252, 0.25);
        }

        .btn-primary {
            background-color: #0098FC !important;
            border-color: #0098FC !important;
            height: 50px;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-primary:hover {
            background-color: #003D64 !important;
            border-color: #003D64 !important;
        }

        @media (max-width: 768px) {
            .login-image {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <img src="https://publicidadeja.com.br/wp-content/uploads/2025/02/Logo-ZapLocal-fundo-escuro-1-1.png" alt="ZapLocal Logo" class="logo">
            
            <div class="form-box">
                <?php if (isset($erro_cadastro)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($erro_cadastro); ?></div>
                <?php endif; ?>

                <?php if (isset($sucesso_cadastro)): ?>
                    <div class="alert alert-success"><?php echo $sucesso_cadastro; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="nome" name="nome" placeholder="Seu nome completo" required>
                    </div>

                    <div class="mb-3">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Seu e-mail" required>
                    </div>

                    <div class="mb-3">
                        <input type="tel" class="form-control" id="telefone" name="telefone" placeholder="Seu telefone" required>
                    </div>

                    <div class="mb-3">
                        <input type="password" class="form-control" id="senha" name="senha" placeholder="Sua senha" required>
                    </div>

                    <div class="mb-3">
                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" placeholder="Confirme sua senha" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Criar Conta
                    </button>
                </form>

                <p class="mt-3 text-center">Já tem uma conta? <a href="login.php" style="color: #009aff;">Faça login aqui</a></p>
            </div>
        </div>
        <div class="login-image"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>