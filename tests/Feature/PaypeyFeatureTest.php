<?php

namespace Gcorpllc\Paypey\Tests\Feature;

use Gcorpllc\Paypey\Events\TransactionCreated;
use Gcorpllc\Paypey\Exceptions\InvalidAmountException;
use Gcorpllc\Paypey\Exceptions\VerificationFailedException;
use Gcorpllc\Paypey\Facades\Paypey;
use Gcorpllc\Paypey\Models\Transaction;
use Gcorpllc\Paypey\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

class PaypeyFeatureTest extends TestCase
{
    public function test_via_alias_and_sandbox_request()
    {
        $response = Paypey::via('sandbox')
            ->amount(50000)
            ->callbackUrl('https://example.com/callback')
            ->description('Test Order')
            ->request();

        $this->assertTrue($response->isSuccessful());
        $this->assertNotNull($response->getAuthority());
        $this->assertStringContainsString('/paypey/sandbox/redirect/', $response->getRedirectUrl());

        $this->assertDatabaseHas('paypey_transactions', [
            'gateway' => 'sandbox',
            'amount' => 50000,
            'status' => 'pending',
        ]);
    }

    public function test_purchase_method_alias()
    {
        $response = Paypey::via('sandbox')
            ->amount(10000)
            ->callbackUrl('https://example.com/callback')
            ->purchase();

        $this->assertTrue($response->isSuccessful());
    }

    public function test_invalid_amount_throws_exception()
    {
        $this->expectException(InvalidAmountException::class);

        Paypey::via('sandbox')->amount(0)->request();
    }

    public function test_zarinpal_request_and_toman_rial_conversion()
    {
        Event::fake([TransactionCreated::class]);

        Http::fake([
            'https://sandbox.zarinpal.com/pg/v4/payment/request.json' => Http::response([
                'data' => [
                    'code' => 100,
                    'authority' => 'A00000000000000000000000000000012345',
                ],
            ], 200),
        ]);

        $response = Paypey::via('zarinpal')
            ->amount(10000) // 10000 Toman
            ->callbackUrl('https://example.com/callback')
            ->request();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('A00000000000000000000000000000012345', $response->getAuthority());

        Http::assertSent(function ($request) {
            return $request['amount'] === 10000; // Zarinpal config target currency is Toman
        });

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_saman_currency_conversion_toman_to_rial()
    {
        Http::fake([
            'https://sep.shaparak.ir/onlinepg/onlinepg' => Http::response([
                'status' => 1,
                'token' => 'SAMAN_TOKEN_123',
            ], 200),
        ]);

        $response = Paypey::via('saman')
            ->amount(5000) // 5000 Toman
            ->callbackUrl('https://example.com/callback')
            ->request();

        $this->assertTrue($response->isSuccessful());

        Http::assertSent(function ($request) {
            return (int) $request['Amount'] === 50000; // Converted to 50000 Rials
        });
    }

    public function test_faking_engine_default_success()
    {
        Paypey::fake();

        $response = Paypey::via('zarinpal')
            ->amount(20000)
            ->request();

        $this->assertTrue($response->isSuccessful());

        $receipt = Paypey::via('zarinpal')->verify(['Authority' => $response->getAuthority()]);
        $this->assertTrue($receipt->isSuccessful());
    }

    public function test_faking_engine_custom_expectations()
    {
        Paypey::fake([
            'zarinpal' => Paypey::fakeFailed('Card balance insufficient'),
        ]);

        $this->expectException(VerificationFailedException::class);

        Paypey::via('zarinpal')->verify(['Authority' => 'A123']);
    }

    public function test_custom_driver_extension()
    {
        Paypey::extend('custom_gateway', function ($app) {
            return new class extends \Gcorpllc\Paypey\Drivers\AbstractDriver {
                public function getDriverName(): string { return 'custom_gateway'; }
                protected function getTargetCurrency(): string { return 'toman'; }
                public function request(): \Gcorpllc\Paypey\Contracts\GatewayResponseInterface {
                    return new \Gcorpllc\Paypey\Classes\GatewayResponse(true, 'CUSTOM_123', 'CUSTOM_123', 'https://custom.test');
                }
                public function verify(?array $params = null): \Gcorpllc\Paypey\Contracts\ReceiptInterface {
                    return new \Gcorpllc\Paypey\Classes\Receipt(true, 'CUSTOM_123', 'REF_CUSTOM', $this->getDriverName(), 1000);
                }
            };
        });

        $response = Paypey::via('custom_gateway')->amount(1000)->request();
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('https://custom.test', $response->getRedirectUrl());
    }

    public function test_transaction_model_helper_scopes()
    {
        Transaction::create(['uuid' => 'u1', 'gateway' => 'zarinpal', 'amount' => 100, 'status' => 'successful']);
        Transaction::create(['uuid' => 'u2', 'gateway' => 'zarinpal', 'amount' => 100, 'status' => 'failed']);
        Transaction::create(['uuid' => 'u3', 'gateway' => 'zarinpal', 'amount' => 100, 'status' => 'pending']);

        $this->assertEquals(1, Transaction::successful()->count());
        $this->assertEquals(1, Transaction::failed()->count());
        $this->assertEquals(1, Transaction::pending()->count());
    }

    public function test_auto_verification_driver_detection()
    {
        Transaction::create([
            'uuid' => 'u100',
            'gateway' => 'idpay',
            'amount' => 15000,
            'status' => 'pending',
            'transaction_id' => 'IDPAY_TX_99',
        ]);

        Http::fake([
            'https://api.idpay.ir/v1.1/payment/verify' => Http::response([
                'status' => 100,
                'track_id' => 'TRACK_123',
                'payment' => ['card_no' => '6037********1234'],
            ], 200),
        ]);

        $receipt = Paypey::verify([
            'id' => 'IDPAY_TX_99',
            'status' => 100,
            'order_id' => 'ORD_1',
        ]);

        $this->assertTrue($receipt->isSuccessful());
        $this->assertEquals('idpay', $receipt->getGateway());
    }
}
