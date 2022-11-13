<?php

namespace App\Service\Request;

use App\Model\Dto\WebRequestParameter;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class CurlRequestService implements WebRequestServiceInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function request(string $url): Response
    {
        $this->logger->info('-- Starting web request through CurlRequestService to : ' . $url);

        $httpCode = HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR;
        $result = null;
        $headers = [];

        try {
            $handle = curl_init();
            curl_setopt($handle, CURLOPT_USERAGENT, WebRequestParameter::USER_AGENT_STRING);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($handle, CURLOPT_URL, $url);
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

            $result = curl_exec($handle);

            $httpCode = curl_getinfo(
                $handle,
                CURLINFO_RESPONSE_CODE
            ) ?? HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR;
            $loadTime = curl_getinfo($handle, CURLINFO_TOTAL_TIME) ?? null;

            if (null !== $loadTime) {
                // value should be formatted as 'total;dur=' . $loadTime but for simplicity just setting as is
                $headers[WebRequestParameter::HEADER_NAME_SERVER_TIMING] = $loadTime * 1000; // from sec to ms
            }
        } catch (\Throwable $exception) {
            $this->logger->critical($exception);
        } finally {
            curl_close($handle);
        }

        return new Response($httpCode, $headers, $result);
    }
}