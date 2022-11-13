<?php

namespace App\Service;

use App\Model\Dto\CrawlProcessOptionsDto;
use App\Model\Dto\ParserParameter;
use App\Model\Dto\WebRequestParameter;
use App\Model\Exception\AppFailureRateTooHigh;
use App\Model\PageReport;
use App\Model\Report;
use App\Service\Parser\ParserService;
use App\Service\Request\CurlRequestService;
use App\Service\Request\HeadlessChromeRequestService;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class CrawlManager
{
    private Report $crawlReport;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CurlRequestService $curlRequestService,
        private readonly HeadlessChromeRequestService $chromeRequestService,
        private readonly ParserService $parserService
    ) {
    }

    /**
     * Main public method of the service, it should represent the whole process with calls to functions with explicit
     * and easy to understand names.
     *
     * @param CrawlProcessOptionsDto $options
     * @return Report
     * @throws \Exception
     */
    public function crawl(CrawlProcessOptionsDto $options): Report
    {
        $this->initialize($options);
        $url = $options->getUrl();
        // Will be useful to find next URL if a page is a failure or a redirect (that we don't crawl)
        $lastSuccessfulDocument = null;

        while (
            $this->crawlReport->getSuccessfullyCrawledPagesCount() < $options->getPagesToCrawlCount() && null !== $url
        ) {
            $document = null;

            // Let's slow down the request process a bit to go easy on web server and avoid rate limiters
            sleep(2);

            // Get the data
            if ($options->isSingePageApplication()) {
                $response = $this->chromeRequestService->request($url);
            } else {
                $response = $this->curlRequestService->request($url);
            }

            $pageReport = $this->buildPageReport($response, $url);

            // Some responses content from request services could be streams,
            // so we need to cast it to string to get it as string
            $content = $response->getBody();
            if ($response->getBody() instanceof Stream) {
                $content = (string)$response->getBody();
            }

            // Parse the data. we don't want to crawl redirections, only OKs
            if (HttpFoundationResponse::HTTP_OK === $pageReport->getHttpStatusCode() && !empty($content)) {
                $document = $this->buildDomDocument($content);
                $pageReport = $this->crawlPage($document, $pageReport);
                if ($pageReport->getUniqueInternalLinksCount() > 1) {
                    $lastSuccessfulDocument = $document;
                }
            }

            $this->crawlReport->addPage($pageReport);

            // Control failure rate and let's go to the next page if ok
            if ($this->crawlReport->isFailureRateAcceptable()) {
                $url = $this->getNextUrlToCrawl($lastSuccessfulDocument);
            } else {
                $this->finalize((new AppFailureRateTooHigh())->getMessage());
                break;
                // throw new AppFailureRateTooHigh();
            }
        }

        if (!$this->crawlReport->isFinalized()) {
            $this->finalize();
        }
        return $this->crawlReport;
    }

    /**
     * @throws \Exception
     */
    private function crawlPage(
        \DOMDocument $document,
        PageReport $pageReport
    ): PageReport {
        $pageReport->setUniqueInternalLinksCount(
            $this->parserService->countFilteredElements(
                $document,
                'a',
                ParserParameter::INTERNAL_LINK_REGEX
            )
        );
        $pageReport->setUniqueExternalLinksCount(
            $this->parserService->countFilteredElements(
                $document,
                'a',
                ParserParameter::EXTERNAL_LINK_REGEX
            )
        );
        $pageReport->setUniqueImagesCount(
            $this->parserService->countFilteredElements($document, 'img')
        );
        $pageReport->setWordCount($this->parserService->getDocumentWordCount($document));

        $titleValue = $this->parserService->getFirstElementValue($document, 'title');
        $pageReport->setTitleLength(
            null !== $titleValue ? strlen($titleValue) : 0
        );

        return $pageReport;
    }

    /**
     * @throws \Exception
     */
    private function getNextUrlToCrawl(?\DOMDocument $lastSuccessfulDocument): string
    {
        $this->logger->info('-- Get next URL...');

        // Should not happen as failure rate is controlled in main function
        if (null === $lastSuccessfulDocument) {
            return $this->crawlReport->getRootUrl();
        }

        $tries = 1;
        do {
            // We want only internal links to be crawled next
            $randomUrlFromPage = $this->parserService->getAnyElementAttributeValue(
                $lastSuccessfulDocument,
                'a',
                'href',
                ParserParameter::INTERNAL_LINK_REGEX
            );
            $tries++;
        } while (null === $randomUrlFromPage && $tries < 10);

        if (null === $randomUrlFromPage) {
            return $this->crawlReport->getRootUrl();
        }

        return str_contains($randomUrlFromPage, $this->crawlReport->getRootUrl()) ? $this->crawlReport->getRootUrl() :
            $this->crawlReport->getRootUrl() . $randomUrlFromPage;
    }

    /**
     * @throws \Exception
     */
    private function initialize(CrawlProcessOptionsDto $options): void
    {
        $this->logger->info('-- Initialize CrawlProcess');
        $this->crawlReport = new Report($options->getUrl());
    }

    /**
     * @link https://php.net/manual/en/function.libxml-use-internal-errors.php
     *
     * @param string $domContent
     * @return \DOMDocument
     */
    private function buildDomDocument(string $domContent): \DOMDocument
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($domContent);
        libxml_use_internal_errors(false);
        return $doc;
    }

    private function buildPageReport(Response $response, string $url): PageReport
    {
        $pageReport = new PageReport($url);
        $pageReport->setHttpStatusCode($response->getStatusCode());
        $serverTimingHeaderValue = $response->getHeader(WebRequestParameter::HEADER_NAME_SERVER_TIMING);
        if (count($serverTimingHeaderValue) > 0) {
            $pageReport->setPageLoadTime((float)reset($serverTimingHeaderValue));
        }
        return $pageReport;
    }

    private function finalize(?string $errorMessage = null): void
    {
        $this->logger->info('-- Finalize CrawlProcess');
        $this->crawlReport->finalize($errorMessage);
    }
}