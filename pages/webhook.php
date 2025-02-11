<?php
require_once 'includes/stripe-config.php';
require_once 'includes/db.php';
require_once 'includes/mail.php';

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$endpoint_secret = 'seu_webhook_secret';

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpoint_secret
    );
    
    switch ($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;
            
            // Atualizar status da assinatura
            $stmt = $pdo->prepare("INSERT INTO assinaturas (usuario_id, plano_id, status, stripe_subscription_id, data_inicio) VALUES (?, ?, 'ativo', ?, NOW())");
            $stmt->execute([$session->client_reference_id, $plano_id, $session->subscription]);
            
            // Enviar email de confirmação
            enviarEmail(
                $session->customer_email,
                "Assinatura Confirmada",
                "Sua assinatura foi confirmada com sucesso!"
            );
            break;
            
        case 'invoice.payment_failed':
            // Lógica para pagamento falho
            break;
    }
    
    http_response_code(200);
} catch(Exception $e) {
    http_response_code(400);
    exit();
}