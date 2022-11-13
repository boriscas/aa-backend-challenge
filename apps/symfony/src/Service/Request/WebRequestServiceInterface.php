<?php

namespace App\Service\Request;

use GuzzleHttp\Psr7\Response;

/**
 * This interface is used to provide a contract if more ways to request a web page are implemented in the future
 * The response of getWebPage() must be PSR7 standard compliant
 */
interface WebRequestServiceInterface
{
    public function request(string $url): Response;
}