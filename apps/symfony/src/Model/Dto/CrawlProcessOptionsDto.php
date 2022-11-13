<?php

namespace App\Model\Dto;

use Symfony\Component\HttpFoundation\Request;

class CrawlProcessOptionsDto
{
    private ?string $url = null;
    private bool $isSingePageApplication = false;
    private int $pagesToCrawlCount = 6;

    public function initializeFromRequest(
        Request $request
    ): CrawlProcessOptionsDto {
        $this->url = $request->get('url');
        $this->isSingePageApplication = $request->get('spa') ?? false;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(?string $url): CrawlProcessOptionsDto
    {
        $this->url = $url;
        return $this;
    }

    public function isSingePageApplication(): bool
    {
        return $this->isSingePageApplication;
    }

    public function getPagesToCrawlCount(): int
    {
        return $this->pagesToCrawlCount;
    }

    public function setPagesToCrawlCount(int $pageToCrawlCount): CrawlProcessOptionsDto
    {
        $this->pagesToCrawlCount = $pageToCrawlCount;
        return $this;
    }
}