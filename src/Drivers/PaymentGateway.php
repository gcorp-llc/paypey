<?php

namespace Gcorpllc\Paypey\Drivers;

use Gcorpllc\Paypey\Payment\PaymentException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /**
     * Set the payment amount.
     *
     * @param int $amount Amount in currency unit (e.g., Rials).
     * @return $this
     */
    public function amount(int $amount): static;

    /**
     * Set the callback URL for payment verification.
     *
     * @param string $callbackUrl
     * @return $this
     */
    public function callbackUrl(string $callbackUrl): static;

    /**
     * Set the payment description.
     *
     * @param string $description
     * @return $this
     */
    public function description(string $description): static;

    /**
     * Set the user's email (optional).
     *
     * @param string|null $email
     * @return $this
     */
    public function email(?string $email): static;

    /**
     * Set the user's mobile number (optional).
     *
     * @param string|null $mobile
     * @return $this
     */
    public function mobile(?string $mobile): static;

    /**
     * Initiate the payment purchase process.
     * Sends request to the gateway and returns a redirect response.
     *
     * @return RedirectResponse
     * @throws \Exception On failure.
     */
    public function purchase(): RedirectResponse;

    /**
     * Verify the payment after callback from the gateway.
     *
     * @param Request $request The callback request object.
     * @return array An array containing verification result (e.g., ['success' => bool, 'refId' => ?, ...])
     * @throws \Exception On failure.
     */
    public function verify(Request $request): array;

    /**
     * Refund a payment.
     * The consuming application must provide the original transaction's authority (or RefId if the gateway supports it and they can map it).
     *
     * @param string $authority The authority of the original transaction to refund.
     * @param int|null $amount Specific amount to refund (if supported by gateway and for partial refunds). Null means full refund.
     * @return array Standardized result array: ['success' => bool, 'refundId' => ?, 'message' => ?, 'errorCode' => ?, 'details' => ?]
     * @throws PaymentException On failure.
     */
    public function reverse(string $authority, ?int $amount = null): array; // Changed to require authority as per Zarinpal API
}
