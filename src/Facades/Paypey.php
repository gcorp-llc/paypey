<?php

namespace Gcorpllc\Paypey\Facades;

use Gcorpllc\Paypey\Contracts\GatewayInterface;
use Gcorpllc\Paypey\Contracts\GatewayResponseInterface;
use Gcorpllc\Paypey\Contracts\ReceiptInterface;
use Gcorpllc\Paypey\PaypeyManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static GatewayInterface via(?string $driver = null)
 * @method static GatewayInterface driver(?string $driver = null)
 * @method static PaypeyManager amount(int|float $amount)
 * @method static PaypeyManager callbackUrl(string $url)
 * @method static PaypeyManager description(string $description)
 * @method static PaypeyManager with(array $metadata)
 * @method static GatewayResponseInterface request()
 * @method static GatewayResponseInterface purchase()
 * @method static ReceiptInterface verify(?array $params = null)
 * @method static PaypeyManager fake(array $expectations = [])
 * @method static ReceiptInterface fakeSuccess(?string $transactionId = null, ?string $refId = null)
 * @method static ReceiptInterface fakeFailed(string $message = 'Fake transaction failed.')
 * @method static PaypeyManager extend(string $driver, \Closure $callback)
 *
 * @see PaypeyManager
 */
class Paypey extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'paypey';
    }
}
