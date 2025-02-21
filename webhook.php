<?php
require_once 'includes/webhook-handler.php';
require_once 'includes/stripe-config.php';
require_once 'includes/db.php';

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, 
        $sig_header, 
        STRIPE_WEBHOOK_SECRET
    );
    
    handleStripeWebhook($event);
    http_response_code(200);
} catch(\UnexpectedValueException $e) {
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit();
} catch (Exception $e) {
    error_log('Erro no webhook: ' . $e->getMessage());
    http_response_code(500);
    exit();
}