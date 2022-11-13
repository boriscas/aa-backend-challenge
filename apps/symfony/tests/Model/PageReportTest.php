<?php

namespace App\Tests\Model;

use App\Model\PageReport;
use PHPUnit\Framework\TestCase;

class PageReportTest extends TestCase
{
    private const PAGE_URL = 'https://firsturl.com/path/example';
    private PageReport $target;

    protected function setUp(): void
    {
        parent::setUp();
        $this->target = new PageReport(self::PAGE_URL);
    }

    public function testCanInstantiate(): void
    {
        // Assert
        $this->assertInstanceOf(PageReport::class, $this->target);
    }
}