<?php

namespace App\Model;

use App\Model\Dto\ParserParameter;
use App\Model\Exception\AppCouldNotDetermineRootUrl;
use Symfony\Component\HttpFoundation\Response;

/**
 * This model holds all the global information of the crawl process
 * The stats are computed on the fly by aggregating or averaging the values for each PageReport
 */
class Report extends AbstractCrawlInfo
{
    private string $rootUrl;
    private \DateTime $startDate;
    private int $durationInSeconds;

    private int $crawlPagesLimit;
    private array $pages;
    private ?string $errorMessage = null;
    private bool $finalized = false;

    public const FAILURE_RATE_THRESHOLD_PERCENTAGE = 50;

    /**
     * @throws \Exception
     */
    public function __construct(string $firstUrl, int $crawlPagesLimit = 6)
    {
        $this->startDate = new \DateTime();
        $this->crawlPagesLimit = $crawlPagesLimit;
        $this->pages = [];
        $this->setRootUrlFromFirstUrl($firstUrl);
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function getPages(): array
    {
        return $this->pages;
    }

    public function getCrawlPagesLimit(): int
    {
        return $this->crawlPagesLimit;
    }

    public function getDurationInSeconds(): int
    {
        return $this->durationInSeconds;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): Report
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getRootUrl(): string
    {
        return $this->rootUrl;
    }

    public function isFinalized(): bool
    {
        return $this->finalized;
    }

    public function addPage(PageReport|array $page): Report
    {
        if (is_array($page)) {
            foreach ($page as $item) {
                if ($item instanceof PageReport) {
                    $item->setNumber(count($this->pages) + 1);
                    $this->pages[] = $item;
                }
            }
        }

        if ($page instanceof PageReport) {
            $page->setNumber(count($this->pages) + 1);
            $this->pages[] = $page;
        }

        return $this;
    }

    private function getSuccessfullyCrawledPages(): array
    {
        return array_filter($this->pages, function (PageReport $page) {
            return $page->getHttpStatusCode() === Response::HTTP_OK;
        });
    }

    public function getSuccessfullyCrawledPagesCount(): int
    {
        return count($this->getSuccessfullyCrawledPages());
    }

    private function getFailedCrawledPages(): array
    {
        return array_filter($this->pages, function (PageReport $page) {
            return $page->getHttpStatusCode() !== Response::HTTP_OK;
        });
    }

    public function getFailedCrawledPagesCount(): int
    {
        return count($this->getFailedCrawledPages());
    }

    /**
     * Get the average of the unique images count of $pages
     *
     * @param string $methodName
     * @return float
     */
    private function computeAverage(string $methodName): float
    {
        $pages = $this->getSuccessfullyCrawledPages();
        $pagesCount = count($pages);
        $sum = 0;

        foreach ($pages as $page) {
            $sum += $page->$methodName();
        }

        return $pagesCount === 0 ? 0 : $sum / $pagesCount;
    }

    public function getUniqueImagesCount(): int
    {
        return intval($this->computeAverage('getUniqueImagesCount'));
    }

    public function getPageLoadTime(): float
    {
        return $this->computeAverage('getPageLoadTime');
    }

    public function getWordCount(): int
    {
        return intval($this->computeAverage('getWordCount'));
    }

    public function getTitleLength(): int
    {
        return intval($this->computeAverage('getTitleLength'));
    }

    public function getUniqueInternalLinksCount(): int
    {
        return intval($this->computeAverage('getUniqueInternalLinksCount'));
    }

    public function getUniqueExternalLinksCount(): int
    {
        return intval($this->computeAverage('getUniqueExternalLinksCount'));
    }

    public function finalize(?string $errorMessage = null): void
    {
        $this->durationInSeconds = (new \DateTime())->getTimestamp() - $this->getStartDate()->getTimestamp();
        $this->errorMessage = $errorMessage;
        $this->finalized = true;
    }

    public function isFailureRateAcceptable(): bool
    {
        $totalPages = count($this->pages);

        // no success with at least 1 page, hard failure...
        if ($this->getSuccessfullyCrawledPagesCount() === 0 && $totalPages > 0) {
            return false;
        }

        return 100 * $this->getFailedCrawledPagesCount() / $totalPages <= self::FAILURE_RATE_THRESHOLD_PERCENTAGE;
    }

    /**
     * @throws \Exception
     */
    private function setRootUrlFromFirstUrl(string $firstUrl): void
    {
        $matches = [];
        $result = preg_match('#' . ParserParameter::URL_DOMAIN_REGEX . '#', $firstUrl, $matches);
        if ($result !== 1 || !isset($matches[0])) {
            throw new AppCouldNotDetermineRootUrl();
        }
        $this->rootUrl = $matches[0];
    }
}