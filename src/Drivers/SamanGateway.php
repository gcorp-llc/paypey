<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Classes\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SamanGateway extends AbstractGateway
{
    public function purchase(): mixed
    {
        $amount = $this->invoice->getAmount();
        if ($this->invoice->getCurrency() === 'IRT') $amount *= 10;

        // Saman use REST now
        $response = Http::post('https://sep.shaparak.ir/api/v1/payment/token', [
            'action' => 'token',
            'terminalId' => $this->config['terminalId'] ?? '',
            'amount' => $amount,
            'callbackUrl' => $this->invoice->getCallbackUrl(),
        ]);

        $result = $response->json();
        if (isset($result['token'])) {
            return view('paypey::saman_redirect', ['token' => $result['token']]);
        }
        throw new \Exception('Saman payment failed');
    }

    public function verify(Request $request): Receipt
    {
        return new Receipt(true, $request->get('RefNum'));
    }

    public function refund(string $transactionId, ?int $amount = null): array
    {
        return ['success' => false];
    }
}
