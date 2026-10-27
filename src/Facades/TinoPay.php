<?php

namespace TinoPay\TinoPay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string qrUrl(string $bankCode, string $accountNo, int|null $amount = null, string|null $memo = null, string|null $accountName = null, string|null $template = null)
 * @method static array banks()
 * @method static array transactions(array $filters = [])
 *
 * @see \TinoPay\TinoPay\TinoPay
 */
class TinoPay extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \TinoPay\TinoPay\TinoPay::class;
    }
}
