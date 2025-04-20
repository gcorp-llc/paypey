<?php

namespace Gcorpllc\Paypey\Payment;

use Illuminate\Support\Facades\Http;

class ZarinpalGateway implements PaymentGateway
{
    protected $amount;
    protected $callbackUrl;

    /**
     * Set the payment amount in IRR.
     *
     * @param int $amount
     * @return $this
     */
    public function amount(int $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Set the callback URL for payment verification.
     *
     * @param string $callbackUrl
     * @return $this
     */
    public function callbackUrl(string $callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;
        return $this;
    }

    public function reverse()
    {
        dd("authority");

    }

    public function purchase()
    {
        // Get configuration values from config/services.php
        $merchantId = config('paypey.gateways.zarinpal.merchantId');
        $callbackUrl = config('paypey.callbackUrl');
        $sandbox = config('paypey.sandbox');

        // Define payment data
        $paymentData = [
            'merchant_id' => $merchantId,
            'amount' => $this->amount, // Amount in Rials
            'callback_url' => $callbackUrl,
            'description' => 'Transaction description.',
            'metadata' => [
                'mobile' => '09121234567',
                'email' => 'info.test@example.com',
            ],
        ];
        // Send POST request to Zarinpal API using GuzzleHttp
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://payment.zarinpal.com/pg/v4/payment/request.json', $paymentData);

        // Check if the request was successful
        if ($response->successful()) {
            $responseData = $response->json();
            return response()->json($responseData); // Return Zarinpal response as JSON
        } else {

            // Handle error response

          return response()->json([
                'error' => 'Failed to connect to Zarinpal API',
                'details' => $response->body(),
            ], $response->status());
        }
    }

}
