<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/stripe-config.php';

// Se houver um ID de assinatura para cancelar
if (isset($_POST['cancelar_assinatura']) && isset($_POST['subscription_id'])) {
    try {
        // Cancelar assinatura no Stripe
        $subscription = \Stripe\Subscription::retrieve($_POST['subscription_id']);
        $subscription->cancel();
        
        // Atualizar status no banco de dados
        $stmt = $pdo->prepare("
            UPDATE assinaturas 
            SET status = 'cancelado', 
                data_fim = NOW() 
            WHERE stripe_subscription_id = ?
        ");
        $stmt->execute([$_POST['subscription_id']]);
        
        $_SESSION['mensagem'] = "Assinatura cancelada com sucesso.";
    } catch (Exception $e) {
        $_SESSION['erro'] = "Erro ao cancelar assinatura: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinatura Cancelada</title>
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
                            <i class="fas fa-times-circle text-danger fa-5x mb-3"></i>
                            <h2 class="card-title">Assinatura Cancelada</h2>
                            <p class="card-text">
                                <?php if (isset($_SESSION['mensagem'])): ?>
                                    <?php 
                                    echo $_SESSION['mensagem'];
                                    unset($_SESSION['mensagem']);
                                    ?>
                                <?php else: ?>
                                    O processo de assinatura foi cancelado ou interrompido.
                                <?php endif; ?>
                            </p>
                            <div class="mt-4">
                                <a href="planos.php" class="btn btn-primary">Voltar para Planos</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>