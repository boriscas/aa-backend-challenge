<?php

namespace App\Tests\Model;

use App\Model\PageReport;
use App\Model\Report;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ReportTest extends TestCase
{
    private const FIRST_URL = 'https://firsturl.com';
    private Report $target;

    protected function setUp(): void
    {
        parent::setUp();
        $this->target = new Report(self::FIRST_URL);
    }

    public function testCanInstantiate(): void
    {
        // Assert
        $this->assertInstanceOf(Report::class, $this->target);
    }

    /**
     * @throws \Exception
     */
    public function testAutoConfigurationFirstUrlWithPathInfo(): void
    {
        // Act
        $this->target = new Report(self::FIRST_URL . '/test/subpath?query=1234');

        // Assert
        $this->assertNotNull($this->target);
        $this->assertEquals(self::FIRST_URL, $this->target->getRootUrl());
        $this->assertInstanceOf(\DateTime::class, $this->target->getStartDate());
    }

    public function testAddPagePage(): void
    {
        // Act
        $this->target->addPage(new PageReport(self::FIRST_URL . '/test/subpath?query=1234'));
        $this->target->addPage(new PageReport(self::FIRST_URL . '/test/subpath/test2'));

        // Assert
        $this->assertCount(2, $this->target->getPages());
    }

    public function testAddPageArray(): void
    {
        // Arrange
        $pages = [
            new PageReport(self::FIRST_URL . '/test/subpath?query=1234'),
            new PageReport(self::FIRST_URL . '/test/subpath/test2')
        ];

        // Act
        $this->target->addPage($pages);

        // Assert
        $this->assertCount(2, $this->target->getPages());
    }

    /**
     * @return void
     */
    public function testGetFailedAndSuccessCrawledPagesCount(): void
    {
        // Arrange
        $this->target->addPage($this->getPagesReportData());

        // Act & Assert
        $this->assertEquals(2, $this->target->getSuccessfullyCrawledPagesCount());
        $this->assertEquals(4, $this->target->getFailedCrawledPagesCount());
    }

    /**
     * @return void
     */
    public function testGetReportInfo(): void
    {
        // Arrange
        $this->target->addPage($this->getPagesReportData());

        // Act & Assert (manually calculated)
        $this->assertEquals(7, $this->target->getUniqueImagesCount());
        $this->assertEquals(175.675, $this->target->getPageLoadTime());
        $this->assertEquals(111, $this->target->getWordCount());
        $this->assertEquals(27, $this->target->getTitleLength());
        $this->assertEquals(15, $this->target->getUniqueInternalLinksCount());
        $this->assertEquals(6, $this->target->getUniqueExternalLinksCount());
    }

    public function testFailureRateIsNotAcceptable(): void
    {
        // Arrange
        $this->target->addPage($this->getPagesReportData());

        // Assert
        $this->assertFalse($this->target->isFailureRateAcceptable());
    }

    public function testFailureRateIsAcceptable(): void
    {
        // Arrange
        $this->target->addPage(
            [
                (new PageReport(self::FIRST_URL . '/test/subpath?query=1234'))
                    ->setHttpStatusCode(Response::HTTP_OK),
                (new PageReport(self::FIRST_URL . '/test/test2')), // everything null
                (new PageReport(self::FIRST_URL . '/test/test3'))
                    ->setHttpStatusCode(Response::HTTP_PERMANENTLY_REDIRECT),
                (new PageReport(self::FIRST_URL . '/test/test6'))
                    ->setHttpStatusCode(Response::HTTP_OK),
                (new PageReport(self::FIRST_URL . '/test/test6'))
                    ->setHttpStatusCode(Response::HTTP_OK),
            ]
        );

        // Assert
        $this->assertTrue($this->target->isFailureRateAcceptable());
    }

    public function testFailureRateIsNotAcceptableFirstPageFails(): void
    {
        // Arrange
        $this->target->addPage([
            (new PageReport(self::FIRST_URL . '/test/test3'))
                ->setHttpStatusCode(Response::HTTP_GATEWAY_TIMEOUT)
        ]);

        // Assert
        $this->assertFalse($this->target->isFailureRateAcceptable());
    }

    public function testFailureRateIsAcceptableSecondOneFails(): void
    {
        // Arrange
        $this->target->addPage([
            (new PageReport(self::FIRST_URL . '/test/test3'))
                ->setHttpStatusCode(Response::HTTP_OK),
            (new PageReport(self::FIRST_URL . '/test/test3'))
                ->setHttpStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
        ]);

        // Assert
        $this->assertTrue($this->target->isFailureRateAcceptable());
    }

    public function testFailureRateIsNotAcceptableSecondAndThirdOneFails(): void
    {
        // Arrange
        $this->target->addPage([
            (new PageReport(self::FIRST_URL . '/test/test3'))
                ->setHttpStatusCode(Response::HTTP_OK),
            (new PageReport(self::FIRST_URL . '/test/test3'))
                ->setHttpStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR),
            (new PageReport(self::FIRST_URL . '/test/test3'))
                ->setHttpStatusCode(Response::HTTP_PERMANENTLY_REDIRECT)
        ]);

        // Assert
        $this->assertFalse($this->target->isFailureRateAcceptable());
    }

    private function getPagesReportData(): array
    {
        // Fluent setters very useful in that kind of test stubs building
        return [
            (new PageReport(self::FIRST_URL . '/test/subpath?query=1234'))
                ->setHttpStatusCode(Response::HTTP_OK)
                ->setUniqueImagesCount(10)
                ->setPageLoadTime(100.5)
                ->setWordCount(177)
                ->setTitleLength(42)
                ->setUniqueInternalLinksCount(14)
                ->setUniqueExternalLinksCount(8)
            ,
            (new PageReport(self::FIRST_URL . '/test/test2')), // everything null
            (new PageReport(self::FIRST_URL . '/test/test3'))
                ->setHttpStatusCode(Response::HTTP_PERMANENTLY_REDIRECT),
            (new PageReport(self::FIRST_URL . '/test/test4'))
                ->setHttpStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR),
            (new PageReport(self::FIRST_URL . '/test/test5'))
                ->setHttpStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR),
            (new PageReport(self::FIRST_URL . '/test/test6'))
                ->setHttpStatusCode(Response::HTTP_OK)
                ->setUniqueImagesCount(5)
                ->setPageLoadTime(250.85)
                ->setWordCount(45)
                ->setTitleLength(13)
                ->setUniqueInternalLinksCount(17)
                ->setUniqueExternalLinksCount(5),
        ];
    }
}