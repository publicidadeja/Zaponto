<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nome'] = $usuario['nome'];
        $_SESSION['perfil_completo'] = $usuario['perfil_completo'];
        header('Location: dashboard.php');
        exit;
    } else {
        $erro_login = "Email ou senha incorretos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ZapLocal</title>
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

        .signup-link {
            color: #003D64;
            text-decoration: none;
            font-weight: 500;
            margin-top: 1rem;
            display: block;
            text-align: center;
        }

        .signup-link:hover {
            color: #0098FC;
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
                <?php if (isset($erro_login)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($erro_login); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Seu e-mail" required>
                    </div>

                    <div class="mb-3">
                        <input type="password" class="form-control" id="senha" name="senha" placeholder="Sua senha" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Entrar
                    </button>
                </form>

                <p class="mt-3">Não tem uma conta? <a href="cadastro.php">Cadastre-se aqui</a>.</p>
<p class="mt-2">Esqueceu sua senha? <a href="recuperar-senha.php" style="color: #009aff;">Recuperar senha</a></p>

            </div>
        </div>
        <div class="login-image"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>