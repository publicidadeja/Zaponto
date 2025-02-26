<?php
// includes/stripe-helpers.php

function getSubscriptionStatus($stripe_subscription_id) {
    try {
        $subscription = \Stripe\Subscription::retrieve($stripe_subscription_id);
        return $subscription->status;
    } catch (Exception $e) {
        error_log("Erro ao buscar status da assinatura: " . $e->getMessage());
        return null;
    }
}

function getPaymentHistory($stripe_customer_id) {
    try {
        $payments = \Stripe\PaymentIntent::all([
            'customer' => $stripe_customer_id,
            'limit' => 10
        ]);
        return $payments->data;
    } catch (Exception $e) {
        error_log("Erro ao buscar histórico de pagamentos: " . $e->getMessage());
        return [];
    }
}

function updatePaymentMethod($stripe_customer_id, $payment_method_id) {
    try {
        \Stripe\PaymentMethod::attach($payment_method_id, [
            'customer' => $stripe_customer_id,
        ]);
        
        \Stripe\Customer::update($stripe_customer_id, [
            'invoice_settings' => [
                'default_payment_method' => $payment_method_id
            ]
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao atualizar método de pagamento: " . $e->getMessage());
        return false;
    }
}

function createOrUpdateCustomer($usuario) {
    try {
        $customerData = [
            'email' => $usuario['email'],
            'name' => $usuario['nome'],
            'metadata' => [
                'usuario_id' => $usuario['id']
            ]
        ];

        if (!empty($usuario['stripe_customer_id'])) {
            return \Stripe\Customer::update(
                $usuario['stripe_customer_id'],
                $customerData
            );
        } else {
            return \Stripe\Customer::create($customerData);
        }
    } catch (Exception $e) {
        error_log("Erro ao criar/atualizar cliente: " . $e->getMessage());
        throw $e;
    }
}