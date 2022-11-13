<?php

namespace App\Tests\Serice\Parser;

use App\Service\Parser\ParserService;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ParserServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ParserService $target;

    protected function setUp(): void
    {
        parent::setUp();
        $loggerMock = \Mockery::mock(LoggerInterface::class);
        $loggerMock->shouldReceive('info')
            ->zeroOrMoreTimes();
        $this->target = new ParserService($loggerMock);
    }

    public function testCanInstantiate(): void
    {
        // Assert
        $this->assertInstanceOf(ParserService::class, $this->target);
    }

    public function testGetFirstElementValue(): void
    {
        // Arrange
        $title = 'this is the page title';
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>$title</title>
    <link rel="icon"
          href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 128 128%22><text y=%221.2em%22 font-size=%2296%22>⚫️</text></svg>">
</head>
<body>
<div class="mdl-layout__container has-scrolling-header">
    <div class="aa-challenge-layout mdl-layout mdl-layout--fixed-header mdl-js-layout mdl-color--grey-100 is-upgraded" data-upgraded=",MaterialLayout">       
    </div>
</div>
</body>
</html>    
HTML;

        // Act
        $result = $this->target->getFirstElementValue(
            $this->createDocumentForTest($html),
            'title'
        );

        // Assert
        $this->assertEquals($title, $result);
    }

    /**
     * Tough to test and to guarantee that it will be exact with real world like data.
     * Would need a lot more test through different dom content, especially to get exclusions (or inclusions)
     *
     * @return void
     */
    public function testGetDocumentWordCount(): void
    {
        // Arrange
        $html = $this->getTestDocumentWordCountdata();

        // Act
        $result = $this->target->getDocumentWordCount($this->createDocumentForTest($html));

        // Assert
        $this->assertGreaterThan(27, $result);
    }

    public function testGetAnyElementAttributeValue(): void
    {
        // Arrange
        $html = $this->getTestDocumentWordCountdata();

        // Act
        $result = $this->target->getAnyElementAttributeValue(
            $this->createDocumentForTest($html),
            'a',
            'href'
        );

        // Assert
        $this->assertContains($result, [
                'https://github.com/boriscas/aa-backend-challenge',
                'https://www.linkedin.com/in/boris-castagna/'
            ]
        );
    }

    public function testGetAnyElementAttributeValueIfNoMatch(): void
    {
        // Arrange
        $html = $this->getTestDocumentWordCountdata();

        // Act
        $result = $this->target->getAnyElementAttributeValue(
            $this->createDocumentForTest($html),
            'notexisting',
            'something'
        );

        // Assert
        $this->assertNull($result);
    }

    public function testGetAnyElementAttributeValueIfNoMatchAttribute(): void
    {
        // Arrange
        $html = $this->getTestDocumentWordCountdata();

        // Act
        $result = $this->target->getAnyElementAttributeValue(
            $this->createDocumentForTest($html),
            'a',
            'something'
        );

        // Assert
        $this->assertNull($result);
    }

    // + countFiltered
    // + testUnique with 4 img and 2 equals => 3

    private function createDocumentForTest(string $domContent): \DOMDocument
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($domContent);
        libxml_use_internal_errors(false);
        return $doc;
    }

    private function getTestDocumentWordCountData(): string
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