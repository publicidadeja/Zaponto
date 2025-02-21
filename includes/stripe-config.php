<?php
// includes/stripe-config.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

// Configurar Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Função helper para formatar valores para o Stripe (em centavos)
function formatStripeAmount($amount) {
    return (int)($amount * 100);
}

// Função para criar ou atualizar cliente no Stripe
function createOrUpdateStripeCustomer($usuario) {
    try {
        $customerData = [
            'email' => $usuario['email'],
            'name' => $usuario['nome'],
            'metadata' => [
                'usuario_id' => $usuario['id']
            ]
        ];

        if (!empty($usuario['stripe_customer_id'])) {
            $customer = \Stripe\Customer::update(
                $usuario['stripe_customer_id'],
                $customerData
            );
        } else {
            $customer = \Stripe\Customer::create($customerData);
        }

        return $customer;
    } catch (\Exception $e) {
        error_log('Erro ao criar/atualizar cliente no Stripe: ' . $e->getMessage());
        throw $e;
    }
}

// Função para criar sessão de checkout
function createCheckoutSession($plano, $customer_id, $usuario_id) {
    try {
        return \Stripe\Checkout\Session::create([
            'customer' => $customer_id,
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
                'plano_id' => $plano['id']
            ],
            'client_reference_id' => $usuario_id,
            'allow_promotion_codes' => true,
            'billing_address_collection' => 'required',
            'customer_update' => [
                'address' => 'auto',
                'payment_method' => 'auto',
            ],
        ]);
    } catch (\Exception $e) {
        error_log('Erro ao criar sessão de checkout: ' . $e->getMessage());
        throw $e;
    }
}

// Função para cancelar assinatura
function cancelarAssinatura($subscription_id) {
    try {
        $subscription = \Stripe\Subscription::retrieve($subscription_id);
        return $subscription->cancel();
    } catch (\Exception $e) {
        error_log('Erro ao cancelar assinatura: ' . $e->getMessage());
        throw $e;
    }
}

// Função para verificar status da assinatura
function verificarStatusAssinatura($subscription_id) {
    try {
        $subscription = \Stripe\Subscription::retrieve($subscription_id);
        return $subscription->status;
    } catch (\Exception $e) {
        error_log('Erro ao verificar status da assinatura: ' . $e->getMessage());
        throw $e;
    }
}

function handleStripeWebhook($event) {
    global $pdo;
    
    switch ($event->type) {
        case 'invoice.payment_succeeded':
            $subscription = $event->data->object;
            
            // Buscar usuário pelo Stripe Customer ID
            $stmt = $pdo->prepare("
                SELECT id FROM usuarios 
                WHERE stripe_customer_id = ?
            ");
            $stmt->execute([$subscription->customer]);
            $usuario = $stmt->fetch();
            
            if ($usuario) {
                // Renovar limites
                renovarLimitesUsuario($pdo, $usuario['id']);
                
                // Atualizar status da assinatura
                $stmt = $pdo->prepare("
                    UPDATE assinaturas 
                    SET status = 'ativo',
                        proximo_pagamento = ?
                    WHERE usuario_id = ? 
                    AND stripe_subscription_id = ?
                ");
                $stmt->execute([
                    date('Y-m-d H:i:s', $subscription->current_period_end),
                    $usuario['id'],
                    $subscription->id
                ]);
            }
            break;
            
        case 'invoice.payment_failed':
            // Implementar lógica para pagamento falho
            break;
    }
}