<?php
require_once '../includes/stripe-config.php';
require_once '../includes/db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plano_id = $_POST['plano_id'];
    $usuario_id = $_SESSION['usuario_id'];
    
    // Buscar plano
    $stmt = $pdo->prepare("SELECT * FROM planos WHERE id = ?");
    $stmt->execute([$plano_id]);
    $plano = $stmt->fetch();
    
    try {
        // Criar sessão de checkout
        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $plano['stripe_price_id'],
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => 'https://seusite.com/sucesso?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'https://seusite.com/cancelado',
            'customer_email' => $_SESSION['usuario_email']
        ]);
        
        // Redirecionar para página de checkout do Stripe
        header("Location: " . $checkout_session->url);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['erro'] = $e->getMessage();
        header('Location: planos.php');
        exit;
    }
}