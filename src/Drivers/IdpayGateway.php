<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Classes\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IdpayGateway extends AbstractGateway
{
    protected string $url = 'https://api.idpay.ir/v1.1/payment';

    public function purchase(): mixed
    {
        $data = [
            'order_id' => time(),
            'amount' => $this->invoice->getAmount(),
            'callback' => $this->invoice->getCallbackUrl(),
            'desc' => $this->invoice->getDescription(),
        ];

        if ($this->invoice->getCurrency() === 'IRT') {
            $data['amount'] *= 10;
        }

        $response = Http::withHeaders([
            'X-API-KEY' => $this->config['apiKey'] ?? '',
            'X-SANDBOX' => $this->config['sandbox'] ? '1' : '0',
        ])->post($this->url, $data);

        $result = $response->json();

        if (isset($result['link'])) {
            return redirect($result['link']);
        }

        throw new \Exception($result['error_message'] ?? 'IDPay request failed');
    }

    public function verify(Request $request): Receipt
    {
        $id = $request->get('id');
        $orderId = $request->get('order_id');

        $response = Http::withHeaders([
            'X-API-KEY' => $this->config['apiKey'] ?? '',
            'X-SANDBOX' => $this->config['sandbox'] ? '1' : '0',
        ])->post($this->url . '/verify', [
            'id' => $id,
            'order_id' => $orderId,
        ]);

        $result = $response->json();

        if (isset($result['status']) && $result['status'] == 100) {
            $receipt = new Receipt(true, $id);
            $receipt->referenceId($result['track_id']);
            return $receipt;
        }

        return new Receipt(false);
    }

    public function refund(string $transactionId, ?int $amount = null): array
    {
        return ['success' => false, 'message' => 'Refund not supported by IDPay API yet'];
    }
}
