<?php

namespace Gcorpllc\Paypey\Payment;

use Gcorpllc\Paypey\Contracts\Gateway;
use Gcorpllc\Paypey\Models\PaymentGateway as GatewayModel;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class PaymentManager extends Manager
{
    public function getDefaultDriver()
    {
        return config('paypey.default_gateway', 'zarinpal');
    }

    protected function createDriver($driver)
    {
        $config = $this->getGatewayConfig($driver);

        $method = 'create' . ucfirst($driver) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method($config);
        }

        return parent::createDriver($driver);
    }

    protected function getGatewayConfig(string $driver): array
    {
        // 1. Priority: Database
        try {
            $dbGateway = GatewayModel::where('driver', $driver)->first();
            if ($dbGateway && $dbGateway->is_active) {
                return $dbGateway->settings;
            }
        } catch (\Exception $e) {
            // Table might not exist yet
        }

        // 2. Priority: Config (which includes ENV via env() calls)
        return config("paypey.gateways.{$driver}", []);
    }

    public function createZarinpalDriver(array $config): Gateway
    {
        return new \Gcorpllc\Paypey\Drivers\ZarinpalGateway($config);
    }

    public function createMellatDriver(array $config): Gateway
    {
        return new \Gcorpllc\Paypey\Drivers\MellatGateway($config);
    }

    public function createSamanDriver(array $config): Gateway
    {
        return new \Gcorpllc\Paypey\Drivers\SamanGateway($config);
    }

    public function createSadadDriver(array $config): Gateway
    {
        return new \Gcorpllc\Paypey\Drivers\SadadGateway($config);
    }

    public function createParsianDriver(array $config): Gateway
    {
        return new \Gcorpllc\Paypey\Drivers\ParsianGateway($config);
    }

    public function createIdpayDriver(array $config): Gateway
    {
        return new \Gcorpllc\Paypey\Drivers\IdpayGateway($config);
    }

    public function createStripeDriver(array $config): Gateway
    {
        return new \Gcorpllc\Paypey\Drivers\StripeGateway($config);
    }

    public function createPaypalDriver(array $config): Gateway
    {
        return new \Gcorpllc\Paypey\Drivers\PaypalGateway($config);
    }
}
