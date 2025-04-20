<?php

namespace Gcorpllc\Paypey\Facades;

use Illuminate\Support\Facades\Facade;

class Paypey extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'paypey'; // باید با کلید سرویس در ServiceProvider مطابقت داشته باشد
    }
}
