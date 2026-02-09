<?php

namespace Gcorpllc\Paypey\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Gcorpllc\Paypey\Providers\PaypeyServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            PaypeyServiceProvider::class,
        ];
    }
}
