<?php

namespace App\Model\Exception;

/**
 * Custom exception to better handle failures, logging, translations of messages...
 */
class AppFailureRateTooHigh extends \Exception
{
    public function __construct()
    {
        parent::__construct();
        $this->message = 'Failure rate too high. Stopping Crawl Process';
    }
}