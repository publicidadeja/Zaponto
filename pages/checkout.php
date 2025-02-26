<?php
// pages/checkout.php

require_once '../includes/stripe-config.php';
require_once '../includes/db.php';

session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plano_id = $_POST['plano_id'];
    $usuario_id = $_SESSION['usuario_id'];
    
    try {
        // Buscar plano
        $stmt = $pdo->prepare("SELECT * FROM planos WHERE id = ?");
        $stmt->execute([$plano_id]);
        $plano = $stmt->fetch();
        
        if (!$plano) {
            throw new Exception("Plano não encontrado");
        }

        // Buscar ou criar cliente no Stripe
        $stmt = $pdo->prepare("SELECT stripe_customer_id FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $usuario = $stmt->fetch();

        if (!$usuario['stripe_customer_id']) {
            // Criar cliente no Stripe
            $customer = \Stripe\Customer::create([
                'email' => $_SESSION['usuario_email'],
                'metadata' => [
                    'usuario_id' => $usuario_id
                ]
            ]);

            // Atualizar usuario com stripe_customer_id
            $stmt = $pdo->prepare("UPDATE usuarios SET stripe_customer_id = ? WHERE id = ?");
            $stmt->execute([$customer->id, $usuario_id]);

            $stripe_customer_id = $customer->id;
        } else {
            $stripe_customer_id = $usuario['stripe_customer_id'];
        }
        
        // Criar sessão de checkout
        $checkout_session = \Stripe\Checkout\Session::create([
            'customer' => $stripe_customer_id,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $plano['stripe_price_id'],
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => SITE_URL . '/pages/sucesso.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => SITE_URL . '/pages/cancelado.php',
            'metadata' => [
                'usuario_id' => $usuario_id,
                'plano_id' => $plano_id
            ],
            'client_reference_id' => $usuario_id,
            'allow_promotion_codes' => true,
            'billing_address_collection' => 'required',
            'customer_update' => [
                'address' => 'auto',
                'payment_method' => 'auto',
            ],
        ]);

        // Salvar informações temporárias na sessão
        $_SESSION['checkout_id'] = $checkout_session->id;
        $_SESSION['plano_selecionado'] = $plano_id;
        
        // Redirecionar para página de checkout do Stripe
        header("Location: " . $checkout_session->url);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['erro'] = $e->getMessage();
        error_log("Erro no checkout: " . $e->getMessage());
        header('Location: planos.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php if (isset($_SESSION['erro'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['erro'];
                        unset($_SESSION['erro']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body text-center">
                        <h3>Processando seu pedido...</h3>
                        <p>Por favor, aguarde...</p>
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Carregando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Submeter o formulário automaticamente
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const planoId = urlParams.get('plano_id');
            
            if (planoId) {
                const form = document.createElement('form');
                form.method = 'POST';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'plano_id';
                input.value = planoId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        });
    </script>
</body>
</html>