<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a PaymentIntent for an order
     *
     * @param int $orderId
     * @param int $amountInCents Amount in cents (e.g., 10000 for $100.00)
     * @param string $currency Currency code (default: USD)
     * @param array $metadata Additional metadata
     * @return PaymentIntent
     * @throws ApiErrorException
     */
    public function createPaymentIntent(int $orderId, int $amountInCents, string $currency = 'USD', array $metadata = []): PaymentIntent
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => strtolower($currency),
                'metadata' => array_merge([
                    'order_id' => $orderId,
                ], $metadata),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            Log::info('PaymentIntent created', [
                'payment_intent_id' => $paymentIntent->id,
                'order_id' => $orderId,
                'amount' => $amountInCents,
            ]);

            return $paymentIntent;
        } catch (ApiErrorException $e) {
            Log::error('Stripe PaymentIntent creation failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a refund for a charge
     *
     * @param string $chargeId Stripe charge ID
     * @param int|null $amountInCents Amount to refund in cents (null = full refund)
     * @return Refund
     * @throws ApiErrorException
     */
    public function createRefund(string $chargeId, ?int $amountInCents = null): Refund
    {
        try {
            $params = [
                'charge' => $chargeId,
            ];

            if ($amountInCents !== null) {
                $params['amount'] = $amountInCents;
            }

            $refund = Refund::create($params);

            Log::info('Stripe refund created', [
                'refund_id' => $refund->id,
                'charge_id' => $chargeId,
                'amount' => $amountInCents,
            ]);

            return $refund;
        } catch (ApiErrorException $e) {
            Log::error('Stripe refund failed', [
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve a PaymentIntent by ID
     *
     * @param string $paymentIntentId
     * @return PaymentIntent
     * @throws ApiErrorException
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    /**
     * Get charge ID from PaymentIntent
     *
     * @param string $paymentIntentId
     * @return string|null
     * @throws ApiErrorException
     */
    public function getChargeIdFromPaymentIntent(string $paymentIntentId): ?string
    {
        // Retrieve PaymentIntent with expanded charges
        $paymentIntent = PaymentIntent::retrieve([
            'id' => $paymentIntentId,
            'expand' => ['charges'],
        ]);
        
        // Get the charge ID from the first charge
        if (empty($paymentIntent->charges->data)) {
            return null;
        }

        return $paymentIntent->charges->data[0]->id ?? null;
    }
}

