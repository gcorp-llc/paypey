<?php

namespace Gcorpllc\Paypey;

use Gcorpllc\Paypey\Classes\Receipt;
use Gcorpllc\Paypey\Contracts\GatewayInterface;
use Gcorpllc\Paypey\Contracts\GatewayResponseInterface;
use Gcorpllc\Paypey\Contracts\ReceiptInterface;
use Gcorpllc\Paypey\Drivers\IdpayDriver;
use Gcorpllc\Paypey\Drivers\MellatDriver;
use Gcorpllc\Paypey\Drivers\NextpayDriver;
use Gcorpllc\Paypey\Drivers\ParsianDriver;
use Gcorpllc\Paypey\Drivers\PaypalDriver;
use Gcorpllc\Paypey\Drivers\SamanDriver;
use Gcorpllc\Paypey\Drivers\SandboxDriver;
use Gcorpllc\Paypey\Drivers\StripeDriver;
use Gcorpllc\Paypey\Drivers\ZarinpalDriver;
use Gcorpllc\Paypey\Exceptions\GatewayException;
use Gcorpllc\Paypey\Exceptions\VerificationFailedException;
use Gcorpllc\Paypey\Models\Transaction;
use Illuminate\Support\Manager;

class PaypeyManager extends Manager implements GatewayInterface
{
    protected bool $isFaking = false;
    protected array $fakeExpectations = [];

    public function getDefaultDriver(): string
    {
        return $this->config->get('paypey.default', 'zarinpal');
    }

    /**
     * Exact alias for driver().
     */
    public function via(?string $driver = null): GatewayInterface
    {
        return $this->driver($driver);
    }

    public function driver($driver = null): GatewayInterface
    {
        $driverName = $driver ?: $this->getDefaultDriver();

        if ($this->isFaking) {
            $expectation = $this->fakeExpectations[$driverName] ?? $this->fakeExpectations['*'] ?? null;
            return new FakeGateway($driverName, $expectation);
        }

        return parent::driver($driverName);
    }

    /**
     * Enable faking mode for tests.
     */
    public function fake(array $expectations = []): static
    {
        $this->isFaking = true;
        $this->fakeExpectations = $expectations;
        return $this;
    }

    public function fakeSuccess(?string $transactionId = null, ?string $refId = null): ReceiptInterface
    {
        return new Receipt(
            success: true,
            transactionId: $transactionId ?: 'FAKE_TX_12345',
            refId: $refId ?: 'FAKE_REF_67890',
            gateway: 'fake',
            amount: 10000,
            cardNumber: '6037********1234',
            message: 'Fake transaction succeeded.',
            rawResponse: ['fake' => true]
        );
    }

    public function fakeFailed(string $message = 'Fake transaction failed.'): ReceiptInterface
    {
        return new Receipt(
            success: false,
            transactionId: null,
            refId: null,
            gateway: 'fake',
            amount: 10000,
            message: $message,
            rawResponse: ['fake' => true, 'error' => $message]
        );
    }

    /**
     * Verify payment with automatic driver detection if not specified.
     */
    public function verify(?array $params = null): ReceiptInterface
    {
        $params = $params ?? request()->all();

        // Attempt driver auto-detection
        $driverName = $this->detectDriverFromParams($params);

        return $this->driver($driverName)->verify($params);
    }

    public function detectDriverFromParams(array $params): string
    {
        // Check for specific authority / transaction_id / token in request
        $authority = $params['Authority'] ?? $params['authority'] ?? $params['trans_id'] ?? $params['transId'] ?? $params['Token'] ?? $params['token'] ?? $params['id'] ?? $params['Id'] ?? $params['RefId'] ?? $params['ref_id'] ?? null;

        if ($authority && config('paypey.database_logging', true)) {
            $tx = Transaction::where('transaction_id', $authority)
                ->orWhere('ref_id', $authority)
                ->first();

            if ($tx) {
                return $tx->gateway;
            }
        }

        // Parameter pattern detection heuristics
        if (isset($params['Authority']) || isset($params['authority'])) {
            return 'zarinpal';
        }

        if (isset($params['RefId']) && isset($params['SaleOrderId'])) {
            return 'mellat';
        }

        if (isset($params['State']) || isset($params['ResNum'])) {
            return 'saman';
        }

        if (isset($params['Token']) && isset($params['RRN'])) {
            return 'parsian';
        }

        if (isset($params['id']) && isset($params['track_id'])) {
            return 'idpay';
        }

        if (isset($params['trans_id'])) {
            return 'nextpay';
        }

        return $this->getDefaultDriver();
    }

    // Driver Creator Methods

    protected function createSandboxDriver(): SandboxDriver
    {
        return new SandboxDriver($this->config->get('paypey.gateways.sandbox', []));
    }

    protected function createZarinpalDriver(): ZarinpalDriver
    {
        return new ZarinpalDriver($this->config->get('paypey.gateways.zarinpal', []));
    }

    protected function createMellatDriver(): MellatDriver
    {
        return new MellatDriver($this->config->get('paypey.gateways.mellat', []));
    }

    protected function createSamanDriver(): SamanDriver
    {
        return new SamanDriver($this->config->get('paypey.gateways.saman', []));
    }

    protected function createParsianDriver(): ParsianDriver
    {
        return new ParsianDriver($this->config->get('paypey.gateways.parsian', []));
    }

    protected function createIdpayDriver(): IdpayDriver
    {
        return new IdpayDriver($this->config->get('paypey.gateways.idpay', []));
    }

    protected function createNextpayDriver(): NextpayDriver
    {
        return new NextpayDriver($this->config->get('paypey.gateways.nextpay', []));
    }

    protected function createStripeDriver(): StripeDriver
    {
        return new StripeDriver($this->config->get('paypey.gateways.stripe', []));
    }

    protected function createPaypalDriver(): PaypalDriver
    {
        return new PaypalDriver($this->config->get('paypey.gateways.paypal', []));
    }

    // Fluent Proxy Methods for default driver

    public function amount(int|float $amount): static
    {
        $this->driver()->amount($amount);
        return $this;
    }

    public function callbackUrl(string $url): static
    {
        $this->driver()->callbackUrl($url);
        return $this;
    }

    public function description(string $description): static
    {
        $this->driver()->description($description);
        return $this;
    }

    public function with(array $metadata): static
    {
        $this->driver()->with($metadata);
        return $this;
    }

    public function request(): GatewayResponseInterface
    {
        return $this->driver()->request();
    }

    public function purchase(): GatewayResponseInterface
    {
        return $this->driver()->purchase();
    }

    public function getDriverName(): string
    {
        return $this->driver()->getDriverName();
    }
}
