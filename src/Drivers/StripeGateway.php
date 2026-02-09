<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Classes\Receipt;
use Illuminate\Http\Request;
use Stripe\StripeClient;

class StripeGateway extends AbstractGateway
{
    protected StripeClient $stripe;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->stripe = new StripeClient($config['secretKey'] ?? '');
    }

    public function purchase(): mixed
    {
        $session = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($this->invoice->getCurrency()),
                    'product_data' => [
                        'name' => $this->invoice->getDescription() ?? 'Payment',
                    ],
                    'unit_amount' => $this->invoice->getAmount() * 100, // Stripe uses cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->invoice->getCallbackUrl() . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->invoice->getCallbackUrl() . '?status=cancel',
        ]);

        return redirect($session->url);
    }

    public function verify(Request $request): Receipt
    {
        $sessionId = $request->get('session_id');
        if (!$sessionId) {
            return new Receipt(false);
        }

        $session = $this->stripe->checkout->sessions->retrieve($sessionId);

        if ($session->payment_status === 'paid') {
            $receipt = new Receipt(true, $session->id);
            $receipt->referenceId($session->payment_intent);
            return $receipt;
        }

        return new Receipt(false);
    }

    public function refund(string $transactionId, ?int $amount = null): array
    {
        try {
            $refund = $this->stripe->refunds->create([
                'payment_intent' => $transactionId,
                'amount' => $amount ? $amount * 100 : null,
            ]);
            return ['success' => true, 'refundId' => $refund->id];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
