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
        $byLanguage = [
            'ja' => ['resourceTypes' => ['agency' => 0, 'group' => 0, 'talent' => 0, 'song' => 0], 'groupTypes' => ['boy_group' => 0, 'girl_group' => 0, 'co_ed' => 0, 'unknown' => 0]],
            'en' => ['resourceTypes' => ['agency' => 0, 'group' => 1, 'talent' => 0, 'song' => 0], 'groupTypes' => ['boy_group' => 1, 'girl_group' => 0, 'co_ed' => 0, 'unknown' => 0]],
            'ko' => ['resourceTypes' => ['agency' => 0, 'group' => 1, 'talent' => 1, 'song' => 0], 'groupTypes' => ['boy_group' => 0, 'girl_group' => 0, 'co_ed' => 0, 'unknown' => 1]],
        ];
        $readModel = new WikiStatisticsReadModel(3, $languages, $resourceTypes, $groupTypes, $byLanguage);

        $this->assertSame([
            'total' => 3,
            'languages' => $languages,
            'resourceTypes' => $resourceTypes,
            'groupTypes' => $groupTypes,
            'byLanguage' => $byLanguage,
        ], $readModel->toArray());
    }
}
