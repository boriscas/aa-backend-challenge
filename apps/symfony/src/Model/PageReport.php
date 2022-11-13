<?php

namespace App\Model;

/**
 * This model holds the information of a crawled page
 */
class PageReport extends AbstractCrawlInfo
{
    private string $url;
    private ?int $httpStatusCode = null;
    private int $number;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    public function setHttpStatusCode(?int $httpStatusCode): AbstractCrawlInfo
    {
        $this->httpStatusCode = $httpStatusCode;
        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): PageReport
    {
        $this->number = $number;
        return $this;
    }
}