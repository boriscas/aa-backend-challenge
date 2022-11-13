<?php

namespace App\Tests\Serice;

use App\Model\Dto\CrawlProcessOptionsDto;
use App\Model\Dto\ParserParameter;
use App\Model\Dto\WebRequestParameter;
use App\Model\Report;
use App\Service\CrawlManager;
use App\Service\Parser\ParserService;
use App\Service\Request\CurlRequestService;
use App\Service\Request\HeadlessChromeRequestService;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CrawlManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private CrawlManager $target;
    private MockInterface|LoggerInterface $loggerMock;
    private MockInterface|CurlRequestService $curlRequestServiceMock;
    private MockInterface|HeadlessChromeRequestService $headlessChromeMock;
    private MockInterface|ParserService $parserServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = \Mockery::mock(LoggerInterface::class);
        $this->loggerMock->shouldReceive('info')
            ->zeroOrMoreTimes();
        $this->curlRequestServiceMock = \Mockery::mock(CurlRequestService::class);
        $this->headlessChromeMock = \Mockery::mock(HeadlessChromeRequestService::class);
        $this->parserServiceMock = \Mockery::mock(ParserService::class);

        // Convenient to have the target here for basic unit test but will be redefined in tests for actions on mocks
        $this->target = new CrawlManager(
            $this->loggerMock,
            $this->curlRequestServiceMock,
            $this->headlessChromeMock,
            $this->parserServiceMock
        );
    }

    public function testCanInstantiate(): void
    {
        // Assert
        $this->assertInstanceOf(CrawlManager::class, $this->target);
    }

    /**
     * @throws \Exception
     */
    public function testCanCrawlAndProduceReport(): void
    {
        // Arrange
        $options = (new CrawlProcessOptionsDto())
            ->setUrl('https://potato.test')
            ->setPagesToCrawlCount(1);
        $this->curlRequestServiceMock->shouldReceive('request')
            ->once()
            ->andReturn(
                new Response(
                    \Symfony\Component\HttpFoundation\Response::HTTP_OK,
                    [WebRequestParameter::HEADER_NAME_SERVER_TIMING => 133.37],
                    $this->getDocumentData()
                )
            );
        // The accuracy of the data returned by parser is tested in ParserServiceTest.
        // It should not be the point of this test.
        $this->parserServiceMock->shouldReceive('countFilteredElements')
            ->with(
                \Mockery::type(
                    \DOMDocument::class
                ),
                'a',
                ParserParameter::INTERNAL_LINK_REGEX
            )
            ->once()
            ->andReturn(10);
        $this->parserServiceMock->shouldReceive('countFilteredElements')
            ->with(
                \Mockery::type(\DOMDocument::class),
                'a',
                ParserParameter::EXTERNAL_LINK_REGEX
            )
            ->once()
            ->andReturn(20);
        $this->parserServiceMock->shouldReceive('countFilteredElements')
            ->with(\Mockery::type(\DOMDocument::class), 'img')
            ->once()
            ->andReturn(30);
        $this->parserServiceMock->shouldReceive('getDocumentWordCount')
            ->with(\Mockery::type(\DOMDocument::class))
            ->once()
            ->andReturn(40);
        $this->parserServiceMock->shouldReceive('getFirstElementValue')
            ->with(\Mockery::type(\DOMDocument::class), 'title')
            ->once()
            ->andReturn('title');
        // Next url stub
        $this->parserServiceMock->shouldReceive('getAnyElementAttributeValue')
            ->with(
                \Mockery::type(\DOMDocument::class),
                'a',
                'href',
                ParserParameter::INTERNAL_LINK_REGEX
            )
            ->once()
            ->andReturn('http://some.url');

        // Act
        $report = $this->target->crawl($options);

        // Assert
        $this->assertInstanceOf(Report::class, $report);
        $this->assertTrue($report->isFinalized());
        $this->assertCount(1, $report->getPages());
        $this->assertNull($report->getErrorMessage());
        $this->assertNotNull($report->getStartDate());
        $this->assertNotNull($report->getDurationInSeconds());
        // some data checks
        $this->assertEquals(133.37, $report->getPageLoadTime());
        $this->assertEquals(10, $report->getUniqueInternalLinksCount());
        $this->assertEquals(20, $report->getUniqueExternalLinksCount());
        $this->assertEquals(30, $report->getUniqueImagesCount());
        $this->assertEquals(40, $report->getWordCount());
        $this->assertEquals(5, $report->getTitleLength());
    }

    private function getDocumentData(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AA Backend Challenge</title>
    <link rel="icon"
          href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 128 128%22><text y=%221.2em%22 font-size=%2296%22>⚫️</text></svg>">                            
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://code.getmdl.io/1.3.0/material.indigo-pink.min.css">
    <script defer src="https://code.getmdl.io/1.3.0/material.min.js"></script>
    <style>
        .a-css-class-that-should-not-be-counted {
            width: 100%;
            height: 40vh;
            background-color: #3F51B5;
            flex-shrink: 0;
        }
    </style>
    <script>
        let test = 'some javascript with more words'
    </script>
</head>
<body>
<div>
    <div>
        <div>
            <div>
                <span class="mdl-layout-title">AA Backend Challenge - Boris Castagna</span>
                <div class="mdl-layout-spacer"></div>
                <nav class="mdl-navigation">
                    <a class="mdl-navigation__link mdl-color-text--grey-800"
                       href="https://github.com/boriscas/aa-backend-challenge">
                        <i class="material-icons">open_in_new</i><span>GitHub</span>
                    </a>
                    <a class="mdl-navigation__link mdl-color-text--grey-800"
                       href="https://www.linkedin.com/in/boris-castagna/"
                       target="_blank">
                        <i class="material-icons">open_in_new</i><span>LinkedIn</span>
                    </a>
                </nav>
            </div>
        </div>
        <div class="aa-challenge-ribbon"></div>
        <div class="aa-challenge-main mdl-layout__content">
            <div class="aa-challenge-container mdl-grid">              
                <div class="aa-challenge-content">
                    <h3>Crawl Process</h3>
                    <form method="post">
                        <div class="mdl-textfield">
                            <input class="mdl-textfield__input" type="text" id="url" 
                                name="url" value="https://agencyanalytics.com" readonly>
                            <label class="mdl-textfield__label" for="url">
                            URL of website to crawl
                            </label>
                        </div>
                        <div class="mdl-textfield">
                            <label class="mdl-checkbox" for="spa">
                                <input type="checkbox" id="spa" name="spa" class="mdl-checkbox__input">
                                <span class="mdl-checkbox__label">Is Single Page Application ?</span>
                            </label>
                        </div>
                        <button class="mdl-button" type="submit">
                            GO
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
HTML;
    }
}