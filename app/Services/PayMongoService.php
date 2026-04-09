<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayMongoService
{
    private const BASE_URL = 'https://api.paymongo.com/v1';

    private string $secretKey;
    private string $publicKey;
    private string $webhookSecret;

    public function __construct()
    {
        $this->secretKey     = config('services.paymongo.secret_key', '');
        $this->publicKey     = config('services.paymongo.public_key', '');
        $this->webhookSecret = config('services.paymongo.webhook_secret', '');

        if (empty($this->secretKey)) {
            throw new \RuntimeException(
                'PayMongo is not configured. Please set PAYMONGO_SECRET_KEY in your .env file. ' .
                'Get your keys at https://dashboard.paymongo.com → Developers → API Keys.'
            );
        }
    }

    /**
     * Create a PayMongo Checkout Session for an invoice.
     *
     * @return array{session_id: string, checkout_url: string}
     */
    public function createCheckoutSession(
        Invoice $invoice,
        string $successUrl,
        string $cancelUrl
    ): array {
        $order = $invoice->order()->with(['items.product', 'items.productVariation'])->first();

        if (! $order) {
            throw new \RuntimeException('Invoice has no order for PayMongo checkout.');
        }

        // Build line items from order items (subtotal lines)
        $lineItems = $order->items->map(function ($item) {
            $name = $item->product->name;
            if ($item->productVariation) {
                $name .= ' (' . $item->productVariation->name . ')';
            }
            
            return [
                'currency'    => 'PHP',
                'amount'      => (int) round((float) $item->unit_price * 100), // unit price in centavos
                'description' => $name,
                'name'        => $name,
                'quantity'    => $item->quantity,
            ];
        })->values()->toArray();

        $shippingFee = (float) ($invoice->shipping_fee ?? 0);
        if ($shippingFee > 0) {
            $lineItems[] = [
                'currency'    => 'PHP',
                'amount'      => (int) round($shippingFee * 100),
                'description' => 'Shipping for order '.$order->order_number,
                'name'        => 'Shipping fee',
                'quantity'    => 1,
            ];
        }

        if ($order->discount_amount > 0) {
            $lineItems[] = [
                'currency'    => 'PHP',
                'amount'      => (int) round((float) $order->discount_amount * -100),
                'description' => ($order->discount_type === 'senior' ? 'Senior Citizen' : 'PWD') . ' Discount for ' . $order->order_number,
                'name'        => ($order->discount_type === 'senior' ? 'Senior Citizen' : 'PWD') . ' Discount',
                'quantity'    => 1,
            ];
        }

        $lineTotalCentavos = 0;
        foreach ($lineItems as $li) {
            $lineTotalCentavos += (int) $li['amount'] * (int) $li['quantity'];
        }
        $expectedCentavos = (int) round((float) $invoice->total_amount * 100);
        if ($lineTotalCentavos !== $expectedCentavos) {
            Log::warning('[PayMongoService] Checkout line items total does not match invoice.total_amount', [
                'invoice_id' => $invoice->id,
                'line_total_centavos' => $lineTotalCentavos,
                'invoice_total_centavos' => $expectedCentavos,
            ]);
        }

        $payload = [
            'data' => [
                'attributes' => [
                    'billing'              => null,
                    'send_email_receipt'   => false,
                    'show_description'     => true,
                    'show_line_items'      => true,
                    'line_items'           => $lineItems,
                    'payment_method_types' => ['card', 'gcash', 'paymaya'],
                    'description'          => "Payment for Invoice {$invoice->invoice_number}",
                    'reference_number'     => $invoice->invoice_number,
                    'success_url'          => $successUrl,
                    'cancel_url'           => $cancelUrl,
                    'metadata'             => [
                        'invoice_id' => $invoice->id,
                        'order_id'   => $order->id,
                    ],
                ],
            ],
        ];

        $response = Http::withBasicAuth($this->secretKey, '')
            ->post(self::BASE_URL . '/checkout_sessions', $payload);

        if ($response->failed()) {
            Log::error('[PayMongoService] Checkout session creation failed', [
                'invoice_id' => $invoice->id,
                'status'     => $response->status(),
                'body'       => $response->json(),
            ]);
            throw new \RuntimeException('PayMongo checkout session creation failed: ' . $response->body());
        }

        $data = $response->json('data');

        Log::info('[PayMongoService] Checkout session created', [
            'invoice_id' => $invoice->id,
            'session_id' => $data['id'],
        ]);

        return [
            'session_id'   => $data['id'],
            'checkout_url' => $data['attributes']['checkout_url'],
        ];
    }

    /**
     * Verify the PayMongo webhook signature.
     * PayMongo sends a header: paymongo-signature: t=<timestamp>,te=<sig>,li=<li>
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $header = $request->header('paymongo-signature');
        if (!$header) {
            return false;
        }

        // Parse the signature header
        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = explode('=', $part, 2);
            $parts[$key] = $value;
        }

        if (empty($parts['t']) || empty($parts['te'])) {
            return false;
        }

        $timestamp   = $parts['t'];
        $signature   = $parts['te'];
        $rawBody     = $request->getContent();
        $signedData  = $timestamp . '.' . $rawBody;
        $expected    = hash_hmac('sha256', $signedData, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    /**
     * Fetch a Checkout Session by ID (for status polling fallback).
     */
    public function getCheckoutSession(string $sessionId): array
    {
        $response = Http::withBasicAuth($this->secretKey, '')
            ->get(self::BASE_URL . '/checkout_sessions/' . $sessionId);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch PayMongo session: ' . $response->body());
        }

        return $response->json('data');
    }

    /**
     * Create a generic PayMongo Checkout Session (no Invoice required).
     * Used for wallet top-ups and any other standalone payments.
     *
     * @return array{session_id: string, checkout_url: string}
     */
    public function createGenericCheckoutSession(
        string $description,
        int    $amountCentavos,
        string $successUrl,
        string $cancelUrl,
        array  $metadata = [],
        array  $lineItems = []
    ): array {
        // Fallback to generic description if no specific line items provided
        if (empty($lineItems)) {
            $lineItems = [[
                'currency'    => 'PHP',
                'amount'      => $amountCentavos,
                'description' => $description,
                'name'        => $description,
                'quantity'    => 1,
            ]];
        }

        $payload = [
            'data' => [
                'attributes' => [
                    'billing'              => null,
                    'send_email_receipt'   => false,
                    'show_description'     => true,
                    'show_line_items'      => true,
                    'line_items'           => $lineItems,
                    'payment_method_types' => ['card', 'gcash', 'paymaya'],
                    'description'          => $description,
                    'success_url'          => $successUrl,
                    'cancel_url'           => $cancelUrl,
                    'metadata'             => $metadata,
                ],
            ],
        ];

        $response = Http::withBasicAuth($this->secretKey, '')
            ->post(self::BASE_URL . '/checkout_sessions', $payload);

        if ($response->failed()) {
            Log::error('[PayMongoService] Generic checkout session failed', [
                'description' => $description,
                'status'      => $response->status(),
                'body'        => $response->json(),
            ]);
            throw new \RuntimeException('PayMongo checkout session creation failed: ' . $response->body());
        }

        $data = $response->json('data');

        Log::info('[PayMongoService] Generic checkout session created', [
            'description' => $description,
            'session_id'  => $data['id'],
        ]);

        return [
            'session_id'   => $data['id'],
            'checkout_url' => $data['attributes']['checkout_url'],
        ];
    }
}
