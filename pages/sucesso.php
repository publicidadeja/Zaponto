<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/stripe-config.php';

// Verificar se há session_id do Stripe
if (!isset($_GET['session_id'])) {
    header('Location: planos.php');
    exit;
}

try {
    // Recuperar a sessão do Stripe
    $session = \Stripe\Checkout\Session::retrieve($_GET['session_id']);
    
    // Verificar se o pagamento foi bem-sucedido
    if ($session->payment_status === 'paid') {
        // Atualizar status da assinatura no banco de dados
        $stmt = $pdo->prepare("
            INSERT INTO assinaturas (
                usuario_id, 
                plano_id, 
                status, 
                stripe_subscription_id, 
                data_inicio
            ) VALUES (?, ?, 'ativo', ?, NOW())
        ");
        
        $stmt->execute([
            $_SESSION['usuario_id'],
            $_SESSION['plano_selecionado'],
            $session->subscription
        ]);

        $_SESSION['mensagem'] = "Sua assinatura foi confirmada com sucesso!";
    }
} catch (Exception $e) {
    $_SESSION['erro'] = "Erro ao processar assinatura: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinatura Confirmada</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <?php if (isset($_SESSION['erro'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['erro'];
                        unset($_SESSION['erro']);
                        ?>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <i class="fas fa-check-circle text-success fa-5x mb-3"></i>
                            <h2 class="card-title">Assinatura Confirmada!</h2>
                            <p class="card-text">
                                Parabéns! Sua assinatura foi processada com sucesso.
                                Você já pode começar a usar todos os recursos do seu plano.
                            </p>
                            <div class="mt-4">
                                <a href="dashboard.php" class="btn btn-primary">Ir para Dashboard</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>