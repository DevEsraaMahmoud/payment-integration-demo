<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymobService
{
    protected string $apiKey;
    protected string $iframeId;
    protected string $integratorId;
    protected string $hmacSecret;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.paymob.api_key');
        $this->iframeId = config('services.paymob.iframe_id');
        $this->integratorId = config('services.paymob.integrator_id');
        $this->hmacSecret = config('services.paymob.hmac');
        $this->baseUrl = config('services.paymob.base_url', 'https://accept.paymob.com/api');
    }

    /**
     * Get HTTP client configured with CA bundle for SSL verification
     * This fixes SSL certificate verification issues on Windows
     * 
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function getHttpClient()
    {
        $caBundle = config('services.paymob.ca_bundle', 'C:\\Users\\MSI\\cacert.pem');
        $client = Http::timeout(30);
        
        if (file_exists($caBundle)) {
            $client = $client->withOptions([
                'verify' => $caBundle,
            ]);
        }
        
        return $client;
    }

    /**
     * Authenticate with Paymob API and get auth token
     * 
     * @return string Auth token
     * @throws \Exception
     */
    public function auth(): string
    {
        try {
            $response = $this->getHttpClient()->post("{$this->baseUrl}/auth/tokens", [
                'api_key' => $this->apiKey,
            ]);

            if (!$response->successful()) {
                Log::error('Paymob auth failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Paymob authentication failed: ' . $response->body());
            }

            $data = $response->json();
            
            if (!isset($data['token'])) {
                throw new \Exception('Paymob auth response missing token');
            }

            return $data['token'];
        } catch (\Exception $e) {
            Log::error('Paymob auth exception', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a Paymob order
     * 
     * @param \App\Models\Order $order Laravel Order model
     * @param string $authToken Auth token from auth()
     * @return int Paymob order ID
     * @throws \Exception
     */
    public function createOrder($order, string $authToken): int
    {
        try {
            $amountInCents = (int) ($order->total_amount * 100);

            $response = $this->getHttpClient()->post("{$this->baseUrl}/ecommerce/orders", [
                'auth_token' => $authToken,
                'delivery_needed' => 'false',
                'amount_cents' => $amountInCents,
                'currency' => 'EGP', // Paymob default currency, adjust if needed
                'merchant_order_id' => $order->order_number,
                'items' => [],
            ]);

            if (!$response->successful()) {
                Log::error('Paymob create order failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Paymob order creation failed: ' . $response->body());
            }

            $data = $response->json();
            
            if (!isset($data['id'])) {
                throw new \Exception('Paymob order response missing id');
            }

            return $data['id'];
        } catch (\Exception $e) {
            Log::error('Paymob create order exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a payment key for Paymob iframe
     * 
     * @param int $paymobOrderId Paymob order ID from createOrder()
     * @param int $amountCents Amount in cents
     * @param array $billingData Customer billing information
     * @param string $authToken Auth token from auth()
     * @return string Payment key token
     * @throws \Exception
     */
    public function createPaymentKey(int $paymobOrderId, int $amountCents, array $billingData, string $authToken): string
    {
        try {
            $payload = [
                'auth_token' => $authToken,
                'amount_cents' => $amountCents,
                'expiration' => 3600, // 1 hour expiration
                'order_id' => $paymobOrderId,
                'billing_data' => [
                    'apartment' => 'NA',
                    'email' => $billingData['email'] ?? '',
                    'floor' => 'NA',
                    'first_name' => $billingData['first_name'] ?? '',
                    'street' => $billingData['street'] ?? 'NA',
                    'building' => 'NA',
                    'phone_number' => $billingData['phone_number'] ?? '',
                    'shipping_method' => 'NA',
                    'postal_code' => 'NA',
                    'city' => $billingData['city'] ?? 'NA',
                    'country' => $billingData['country'] ?? 'EG',
                    'last_name' => $billingData['last_name'] ?? '',
                    'state' => 'NA',
                ],
                'currency' => 'EGP',
                'integration_id' => (int) $this->integratorId,
            ];

            $response = $this->getHttpClient()->post("{$this->baseUrl}/acceptance/payment_keys", $payload);

            if (!$response->successful()) {
                Log::error('Paymob create payment key failed', [
                    'paymob_order_id' => $paymobOrderId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Paymob payment key creation failed: ' . $response->body());
            }

            $data = $response->json();
            
            if (!isset($data['token'])) {
                throw new \Exception('Paymob payment key response missing token');
            }

            return $data['token'];
        } catch (\Exception $e) {
            Log::error('Paymob create payment key exception', [
                'paymob_order_id' => $paymobOrderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate Paymob iframe URL
     * 
     * @param string $paymentKey Payment key from createPaymentKey()
     * @return string Full iframe URL
     */
    public function getIframeUrl(string $paymentKey): string
    {
        return "{$this->baseUrl}/acceptance/iframes/{$this->iframeId}?payment_token={$paymentKey}";
    }

    /**
     * Verify HMAC signature from Paymob callback/webhook
     * 
     * Paymob sends HMAC-SHA512 signature. The signature is computed over:
     * - amount_cents
     * - created_at
     * - currency
     * - error_occured
     * - has_parent_transaction
     * - id
     * - integration_id
     * - is_3d_secure
     * - is_auth
     * - is_capture
     * - is_refunded
     * - is_standalone_payment
     * - is_voided
     * - order.id
     * - owner
     * - pending
     * - source_data.pan
     * - source_data.sub_type
     * - source_data.type
     * - success
     * 
     * @param array $payload Callback payload from Paymob
     * @param string $providedHash HMAC hash provided by Paymob
     * @return bool True if signature is valid
     */
    public function verifyHmac(array $payload, string $providedHash): bool
    {
        try {
            // Build the string to hash according to Paymob documentation
            // Note: Adjust the order and fields based on Paymob's actual HMAC specification
            $hmacString = '';
            
            // Common fields that Paymob uses for HMAC
            $fields = [
                'amount_cents',
                'created_at',
                'currency',
                'error_occured',
                'has_parent_transaction',
                'id',
                'integration_id',
                'is_3d_secure',
                'is_auth',
                'is_capture',
                'is_refunded',
                'is_standalone_payment',
                'is_voided',
                'order',
                'owner',
                'pending',
                'source_data',
                'success',
            ];

            // Build HMAC string from payload
            // Paymob typically sends the data in a specific order
            // Adjust this based on Paymob's actual HMAC specification
            foreach ($fields as $field) {
                if (isset($payload[$field])) {
                    $value = $payload[$field];
                    
                    // Handle nested fields
                    if ($field === 'order' && is_array($value) && isset($value['id'])) {
                        $hmacString .= $value['id'];
                    } elseif ($field === 'source_data' && is_array($value)) {
                        // Handle source_data fields
                        if (isset($value['pan'])) {
                            $hmacString .= $value['pan'];
                        }
                        if (isset($value['sub_type'])) {
                            $hmacString .= $value['sub_type'];
                        }
                        if (isset($value['type'])) {
                            $hmacString .= $value['type'];
                        }
                    } else {
                        $hmacString .= is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
                    }
                }
            }

            // Compute HMAC-SHA512
            $computedHash = hash_hmac('sha512', $hmacString, $this->hmacSecret);

            // Compare hashes (timing-safe comparison)
            return hash_equals($computedHash, $providedHash);
        } catch (\Exception $e) {
            Log::error('Paymob HMAC verification exception', [
                'error' => $e->getMessage(),
                'payload_keys' => array_keys($payload),
            ]);
            return false;
        }
    }

    /**
     * Process refund for a Paymob transaction
     * 
     * Note: Paymob refund API may vary. This is a stub implementation.
     * Adjust based on Paymob's actual refund API documentation.
     * 
     * @param string $chargeId Paymob transaction/charge ID
     * @param int|null $amountCents Amount to refund in cents (null = full refund)
     * @param string $authToken Auth token from auth()
     * @return array Refund response
     * @throws \Exception
     */
    public function refund(string $chargeId, ?int $amountCents = null, string $authToken): array
    {
        try {
            // Paymob refund endpoint - adjust URL and payload based on actual API
            $payload = [
                'auth_token' => $authToken,
                'transaction_id' => $chargeId,
            ];

            if ($amountCents !== null) {
                $payload['amount_cents'] = $amountCents;
            }

            // Note: Adjust endpoint based on Paymob's actual refund API
            // Common endpoints: /acceptance/void_refund, /acceptance/refund, etc.
            $response = $this->getHttpClient()->post("{$this->baseUrl}/acceptance/void_refund", $payload);

            if (!$response->successful()) {
                Log::error('Paymob refund failed', [
                    'charge_id' => $chargeId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Paymob refund failed: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Paymob refund exception', [
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

