<?php

namespace LexiConnInternetServices\DigiCert;

use Illuminate\Support\Facades\Log;

/**
 * Class RateLimterService
 *
 * @package LexiConnInternetServices\DigiCert\RateLimterService
 */
class RateLimterService
{
    private int   $limit    = 990; // The limit is 1000, so we'll stop a bit short.
    private int   $interval = 300; // 5 minutes;
    private array $requests = [];
    
    public function __construct(int $limit = 950, int $interval = 300)
    {
        $this->limit    = $limit;
        $this->interval = $interval;
    }
    
    public function checkLimit()
    {
        $this->cleanupList();
        $this->requests[] = time();
        if (count($this->requests) > $this->limit) {
            $this->sleep();
        }
        
        return true;
    }
    
    private function cleanupList()
    {
        $this->requests = array_filter($this->requests, function ($a) {
            return $a >= (time() - $this->interval);
        });
    }
    
    private function sleep()
    {
        $min       = min($this->requests);
        $sleepTime = $this->interval - (time() - $min);
        if ($sleepTime <= 0) {
            return;
        }
        Log::debug("Sleeping for $sleepTime");
        sleep($sleepTime);
    }
}
