<?php

namespace LexiConnInternetServices\DigiCert\Facades;

use Illuminate\Support\Facades\Facade;
use LexiConnInternetServices\DigiCert\RateLimterService;

/**
 * Class RateLimiter
 *
 * @method static bool checkLimit()
 */
class RateLimiter extends Facade
{
    protected static function getFacadeAccessor()
    {
        return RateLimterService::class;
    }
}
