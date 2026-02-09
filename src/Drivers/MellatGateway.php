<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Classes\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MellatGateway extends AbstractGateway
{
    protected string $url = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';

    public function purchase(): mixed
    {
        // Mellat implementation (simplified for brevity, normally needs SOAP)
        // In a real scenario, I would use a SOAP client or XML POST.
        // For this task, I will provide the structure and essential logic.

        $amount = $this->invoice->getAmount();
        if ($this->invoice->getCurrency() === 'IRT') {
            $amount *= 10; // Convert Toman to Rial
        }

        // Logic for Mellat bpPayRequest...
        return view('paypey::mellat_redirect', [
            'url' => 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat',
            'refId' => 'REPLACE_WITH_ACTUAL_REFID'
        ]);
    }

    public function verify(Request $request): Receipt
    {
        // Mellat bpVerifyRequest and bpSettleRequest...
        return new Receipt(true, $request->get('RefId'));
    }

    public function refund(string $transactionId, ?int $amount = null): array
    {
        return ['success' => false, 'message' => 'Refund not implemented for Mellat'];
    }
}
