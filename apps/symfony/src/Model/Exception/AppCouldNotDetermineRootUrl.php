<?php

namespace App\Model\Exception;

/**
 * Custom exception to better handle failures, logging, translations of messages...
 */
class AppCouldNotDetermineRootUrl extends \Exception
{
    public function __construct()
    {
        parent::__construct();
        $this->message = 'Could not set Root Url from first url. Need an URL with the full domain';
    }
}