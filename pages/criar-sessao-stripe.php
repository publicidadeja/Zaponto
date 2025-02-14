<?php
require_once '../vendor/autoload.php';
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    // Receber dados do POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Configurar Stripe
    \Stripe\Stripe::setApiKey('sua_chave_secreta_stripe');
    
    // Criar sessão
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price' => $data['stripePrice'],
            'quantity' => 1,
        ]],
        'mode' => 'subscription',
        'success_url' => 'https://seudominio.com/success?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://seudominio.com/planos.php',
        'metadata' => [
            'plano_id' => $data['planId']
        ]
    ]);
    
    echo json_encode(['id' => $session->id]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}