<?php
require_once 'db.php';
require_once 'functions.php';
require_once 'stripe-config.php';

function handleWebhookEvent($event) {
    global $pdo;
    
    switch ($event->type) {
        case 'invoice.payment_succeeded':
            $subscription = $event->data->object;
            $customer_id = $subscription->customer;
            
            // Buscar usuário pelo Stripe Customer ID
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE stripe_customer_id = ?");
            $stmt->execute([$customer_id]);
            $usuario = $stmt->fetch();
            
            if ($usuario) {
                // Renovar limites do usuário
                renovarLimitesUsuario($pdo, $usuario['id']);
                
                // Registrar log de renovação
                $stmt = $pdo->prepare("
                    INSERT INTO renovacoes_log 
                    (usuario_id, data_renovacao, status) 
                    VALUES (?, NOW(), 'sucesso')
                ");
                $stmt->execute([$usuario['id']]);
            }
            break;
    }
}