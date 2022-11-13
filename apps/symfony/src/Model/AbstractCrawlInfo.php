<?php

namespace App\Model;

/**
 * Abstract model to represent statistics and info about a crawled web page.
 * Both PageReport and Report inherits this model to guarantee that Report will be able to agregate and average these
 * information.
 */
abstract class AbstractCrawlInfo
{
    // All set to null by default because we don't want 0 that could make the stats false
    protected ?int $uniqueImagesCount = null;
    protected ?int $uniqueInternalLinksCount = null;
    protected ?int $uniqueExternalLinksCount = null;
    protected ?int $wordCount = null;
    protected ?int $titleLength = null;
    protected ?float $pageLoadTime = null;

    public function getUniqueImagesCount(): ?int
    {
        return $this->uniqueImagesCount;
    }

    public function setUniqueImagesCount(?int $uniqueImagesCount): AbstractCrawlInfo
    {
        $this->uniqueImagesCount = $uniqueImagesCount;
        return $this;
    }

    public function getUniqueInternalLinksCount(): ?int
    {
        return $this->uniqueInternalLinksCount;
    }

    public function setUniqueInternalLinksCount(?int $uniqueInternalLinksCount): AbstractCrawlInfo
    {
        $this->uniqueInternalLinksCount = $uniqueInternalLinksCount;
        return $this;
    }

    public function getUniqueExternalLinksCount(): ?int
    {
        return $this->uniqueExternalLinksCount;
    }

    public function setUniqueExternalLinksCount(?int $uniqueExternalLinksCount): AbstractCrawlInfo
    {
        $this->uniqueExternalLinksCount = $uniqueExternalLinksCount;
        return $this;
    }

    public function getWordCount(): ?int
    {
        return $this->wordCount;
    }

    public function setWordCount(?int $wordCount): AbstractCrawlInfo
    {
        $this->wordCount = $wordCount;
        return $this;
    }

    public function getTitleLength(): ?int
    {
        return $this->titleLength;
    }

    public function setTitleLength(?int $titleLength): AbstractCrawlInfo
    {
        $this->titleLength = $titleLength;
        return $this;
    }

    public function getPageLoadTime(): ?float
    {
        return $this->pageLoadTime;
    }

    public function setPageLoadTime(?float $pageLoadTime): AbstractCrawlInfo
    {
        $this->pageLoadTime = $pageLoadTime;
        return $this;
    }
}