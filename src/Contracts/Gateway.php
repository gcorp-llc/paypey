<?php

namespace Gcorpllc\Paypey\Contracts;

use Gcorpllc\Paypey\Classes\Invoice;
use Gcorpllc\Paypey\Classes\Receipt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

interface Gateway
{
    /**
     * Set the payment invoice.
     *
     * @param Invoice $invoice
     * @return $this
     */
    public function invoice(Invoice $invoice): static;

    /**
     * Initiate the payment purchase process.
     *
     * @return RedirectResponse|mixed
     */
    public function purchase(): mixed;

    /**
     * Verify the payment.
     *
     * @param Request $request
     * @return Receipt
     */
    public function verify(Request $request): Receipt;

    /**
     * Refund a payment.
     *
     * @param string $transactionId
     * @param int|null $amount
     * @return array
     */
    public function refund(string $transactionId, ?int $amount = null): array;
}
