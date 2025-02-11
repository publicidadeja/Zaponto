<?php
session_start();
include '../includes/db.php';
include '../includes/mail.php'; // Incluindo o arquivo mail.php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // Verificar se o email existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        // Gerar token de recuperação usando a função existente
        $token = gerarTokenRecuperacao($email, $pdo);
        
        if ($token && enviarEmailRecuperacao($email, $token)) {
            $sucesso = "Um link de recuperação foi enviado para seu email. Por favor, verifique sua caixa de entrada e spam.";
        } else {
            $erro = "Erro ao enviar email de recuperação. Tente novamente mais tarde.";
        }
    } else {
        $erro = "Email não encontrado em nossa base de dados.";
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha | ZapLocal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: Roboto, "Helvetica Neue", sans-serif;
            overflow: hidden;
            height: 100vh;
        }
        .container-fluid {
            height: 100vh;
            padding: 0;
        }
        .row {
            height: 100%;
            margin: 0;
        }
        .form-side {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            background-color: #fff;
            z-index: 2;
        }
        .image-side {
            padding: 0;
            position: relative;
            overflow: hidden;
        }
        .image-side::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('https://publicidadeja.com.br/wp-content/uploads/2025/02/TELA-DE-LOGIN-ZAPONTO-2.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .logo {
            max-width: 200px;
            margin-bottom: 2rem;
        }
        .login-form {
            width: 100%;
            max-width: 400px;
        }
        .form-control {
            height: 48px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            background-color: #f8f9fa;
        }
        .form-control:focus {
            border-color: #009aff;
            box-shadow: 0 0 0 0.2rem rgba(0, 154, 255, 0.25);
        }
        .btn-primary {
            height: 48px;
            background-color: #009aff;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0c82d1;
        }
        .alert {
            border-radius: 8px;
        }
        
        /* Melhorias na responsividade */
        @media (max-width: 768px) {
            .image-side {
                display: none;
            }
            .form-side {
                width: 100%;
                max-width: 100%;
            }
        }
        
        @media (min-width: 769px) and (max-width: 1200px) {
            .image-side::before {
                background-size: cover;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Lado esquerdo com formulário -->
            <div class="col-md-5 form-side">
                <img src="https://publicidadeja.com.br/wp-content/uploads/2025/02/Logo-ZapLocal-fundo-escuro-1-1.png" alt="Logo ZapLocal" class="logo">
                
                <div class="login-form">
                    <h2 class="text-center mb-4">Recuperar Senha</h2>

                    <?php if (isset($erro)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
                    <?php endif; ?>

                    <?php if (isset($sucesso)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-4">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   placeholder="Digite seu email">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            Enviar Link de Recuperação
                        </button>

                        <div class="text-center">
                            <a href="login.php" class="text-decoration-none" style="color: #009aff;">
                                Voltar ao Login
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lado direito com imagem -->
            <div class="col-md-7 image-side"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>