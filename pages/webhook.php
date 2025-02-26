<?php
// pages/webhook.php

require_once '../includes/stripe-config.php';
require_once '../includes/db.php';
require_once '../includes/mail.php';

// Receber payload
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, 
        $sig_header, 
        STRIPE_WEBHOOK_SECRET
    );
    
    // Log do evento
    logStripeEvent($event->type, $event->id, $payload);
    
    switch ($event->type) {
        case 'checkout.session.completed':
            handleCheckoutCompleted($event->data->object);
            break;
            
        case 'customer.subscription.updated':
            handleSubscriptionUpdated($event->data->object);
            break;
            
        case 'customer.subscription.deleted':
            handleSubscriptionDeleted($event->data->object);
            break;
            
        case 'invoice.payment_succeeded':
            handlePaymentSucceeded($event->data->object);
            break;
            
        case 'invoice.payment_failed':
            handlePaymentFailed($event->data->object);
            break;
    }
    
    http_response_code(200);
} catch(\UnexpectedValueException $e) {
    error_log('Erro no Webhook: ' . $e->getMessage());
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    error_log('Erro de assinatura do Webhook: ' . $e->getMessage());
    http_response_code(400);
    exit();
} catch(Exception $e) {
    error_log('Erro geral no Webhook: ' . $e->getMessage());
    http_response_code(400);
    exit();
}

function handleCheckoutCompleted($session) {
    global $pdo;
    
    try {
        // Buscar informações do plano e usuário
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$session->metadata->usuario_id]);
        $usuario = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT * FROM planos WHERE id = ?");
        $stmt->execute([$session->metadata->plano_id]);
        $plano = $stmt->fetch();

        // Inserir ou atualizar assinatura
        $stmt = $pdo->prepare("
            INSERT INTO assinaturas (
                usuario_id,
                plano_id,
                status,
                stripe_subscription_id,
                stripe_customer_id,
                data_inicio,
                proximo_pagamento,
                metodo_pagamento
            ) VALUES (?, ?, 'ativo', ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), 'card')
            ON DUPLICATE KEY UPDATE
                status = 'ativo',
                stripe_subscription_id = ?,
                stripe_customer_id = ?,
                data_inicio = NOW(),
                proximo_pagamento = DATE_ADD(NOW(), INTERVAL 1 MONTH),
                metodo_pagamento = 'card'
        ");
        
        $stmt->execute([
            $session->metadata->usuario_id,
            $session->metadata->plano_id,
            $session->subscription,
            $session->customer,
            $session->subscription,
            $session->customer
        ]);

        // Enviar email de confirmação
        enviarEmailAssinatura($usuario['email'], 'confirmacao', [
            'nome' => $usuario['nome'],
            'plano' => $plano['nome']
        ]);
        
    } catch (Exception $e) {
        error_log('Erro ao processar checkout.session.completed: ' . $e->getMessage());
        throw $e;
    }
}

function handleSubscriptionUpdated($subscription) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE assinaturas 
            SET status = ?,
                proximo_pagamento = ?,
                atualizado_em = NOW()
            WHERE stripe_subscription_id = ?
        ");
        
        $proximo_pagamento = date('Y-m-d H:i:s', $subscription->current_period_end);
        
        $stmt->execute([
            $subscription->status,
            $proximo_pagamento,
            $subscription->id
        ]);
        
    } catch (Exception $e) {
        error_log('Erro ao processar customer.subscription.updated: ' . $e->getMessage());
        throw $e;
    }
}

function handleSubscriptionDeleted($subscription) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE assinaturas 
            SET status = 'cancelado',
                data_cancelamento = NOW(),
                atualizado_em = NOW()
            WHERE stripe_subscription_id = ?
        ");
        
        $stmt->execute([$subscription->id]);
        
        // Buscar usuário para enviar email
        $stmt = $pdo->prepare("
            SELECT u.* FROM usuarios u
            JOIN assinaturas a ON a.usuario_id = u.id
            WHERE a.stripe_subscription_id = ?
        ");
        $stmt->execute([$subscription->id]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            enviarEmailAssinatura($usuario['email'], 'cancelamento', [
                'nome' => $usuario['nome']
            ]);
        }
        
    } catch (Exception $e) {
        error_log('Erro ao processar customer.subscription.deleted: ' . $e->getMessage());
        throw $e;
    }
}

function handlePaymentSucceeded($invoice) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE assinaturas 
            SET status = 'ativo',
                ultima_cobranca = NOW(),
                proximo_pagamento = ?,
                tentativas_cobranca = 0
            WHERE stripe_customer_id = ?
        ");
        
        $proximo_pagamento = date('Y-m-d H:i:s', $invoice->next_payment_attempt ?? strtotime('+1 month'));
        
        $stmt->execute([
            $proximo_pagamento,
            $invoice->customer
        ]);
        
    } catch (Exception $e) {
        error_log('Erro ao processar invoice.payment_succeeded: ' . $e->getMessage());
        throw $e;
    }
}

function handlePaymentFailed($invoice) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE assinaturas 
            SET status = 'inadimplente',
                tentativas_cobranca = tentativas_cobranca + 1
            WHERE stripe_customer_id = ?
        ");
        
        $stmt->execute([$invoice->customer]);
        
        // Buscar usuário para enviar email
        $stmt = $pdo->prepare("
            SELECT u.* FROM usuarios u
            JOIN assinaturas a ON a.usuario_id = u.id
            WHERE a.stripe_customer_id = ?
        ");
        $stmt->execute([$invoice->customer]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            enviarEmailAssinatura($usuario['email'], 'falha', [
                'nome' => $usuario['nome'],
                'valor' => number_format($invoice->amount_due / 100, 2, ',', '.'),
                'data_vencimento' => date('d/m/Y', $invoice->next_payment_attempt ?? time())
            ]);
        }
        
    } catch (Exception $e) {
        error_log('Erro ao processar invoice.payment_failed: ' . $e->getMessage());
        throw $e;
    }
}

function logStripeEvent($event_type, $event_id, $data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO stripe_logs (
                event_type,
                event_id,
                data,
                created_at
            ) VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $event_type,
            $event_id,
            $data
        ]);
    } catch (Exception $e) {
        error_log('Erro ao registrar log do Stripe: ' . $e->getMessage());
    }
}