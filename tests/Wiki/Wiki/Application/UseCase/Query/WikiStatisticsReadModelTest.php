<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiStatisticsReadModel;

class WikiStatisticsReadModelTest extends TestCase
{
    public function testSerializesCountsWithoutDroppingZeroCategories(): void
    {
        $languages = ['ja' => 0, 'en' => 1, 'ko' => 2];
        $resourceTypes = ['agency' => 0, 'group' => 2, 'talent' => 1, 'song' => 0];
        $groupTypes = ['boy_group' => 1, 'girl_group' => 0, 'co_ed' => 0, 'unknown' => 1];
        $readModel = new WikiStatisticsReadModel(3, $languages, $resourceTypes, $groupTypes);

        $this->assertSame([
            'total' => 3,
            'languages' => $languages,
            'resourceTypes' => $resourceTypes,
            'groupTypes' => $groupTypes,
        ], $readModel->toArray());
    }
}
