<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Application\UseCase\Query\RegionalReportReadModel;

class RegionalReportReadModelTest extends TestCase
{
    public function testEmptySerialization(): void
    {
        self::assertSame([], (new RegionalReportReadModel([]))->toArray());
    }
}
