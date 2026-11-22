<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Wiki\Wiki\Application\UseCase\Query\GetWikiStatistics\GetWikiStatisticsInterface;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

#[Group('useDb')]
class GetWikiStatisticsTest extends TestCase
{
    public function testEmptyDatabaseReturnsZeroForEveryCategory(): void
    {
        $statistics = app(GetWikiStatisticsInterface::class)->process()->toArray();
        $this->assertSame(0, $statistics['total']);
        $this->assertSame(['ja' => 0, 'ko' => 0, 'en' => 0], $statistics['languages']);
        foreach (['ja', 'ko', 'en'] as $language) {
            $this->assertSame(['agency' => 0, 'group' => 0, 'talent' => 0, 'song' => 0], $statistics['byLanguage'][$language]['resourceTypes']);
            $this->assertSame(['boy_group' => 0, 'girl_group' => 0, 'co_ed' => 0, 'unknown' => 0], $statistics['byLanguage'][$language]['groupTypes']);
        }
    }

    public function testCountsTranslationsAndAllGroupCategoriesWithoutLosingUnclassifiedPages(): void
    {
        $translationSet = '01965bb2-bcc9-7c6f-8b90-89f7f217f999';
        $types = ['boy_group', 'girl_group', 'co_ed', null];
        foreach ($types as $index => $type) {
            CreateWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f10' . $index, 'group', [
                'language' => $index === 0 ? 'ja' : 'ko',
                'slug' => 'gr-test-' . $index,
            ], ['group_type' => $type]);
        }
        CreateWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f200', 'talent', ['language' => 'ja', 'translation_set_identifier' => $translationSet]);
        CreateWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f201', 'talent', ['language' => 'en', 'translation_set_identifier' => $translationSet]);
        CreateWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f300', 'agency');
        CreateWiki::create('01965bb2-bcc9-7c6f-8b90-89f7f217f400', 'song');

        $statistics = app(GetWikiStatisticsInterface::class)->process()->toArray();
        $this->assertSame(8, $statistics['total']);
        $this->assertSame(['ja' => 2, 'ko' => 5, 'en' => 1], $statistics['languages']);
        $this->assertSame(['agency' => 0, 'group' => 1, 'talent' => 1, 'song' => 0], $statistics['byLanguage']['ja']['resourceTypes']);
        $this->assertSame(['boy_group' => 1, 'girl_group' => 0, 'co_ed' => 0, 'unknown' => 0], $statistics['byLanguage']['ja']['groupTypes']);
        $this->assertSame(['agency' => 1, 'group' => 3, 'talent' => 0, 'song' => 1], $statistics['byLanguage']['ko']['resourceTypes']);
        $this->assertSame(['boy_group' => 0, 'girl_group' => 1, 'co_ed' => 1, 'unknown' => 1], $statistics['byLanguage']['ko']['groupTypes']);
        $this->assertSame(['agency' => 0, 'group' => 0, 'talent' => 1, 'song' => 0], $statistics['byLanguage']['en']['resourceTypes']);
        $this->assertSame(['boy_group' => 0, 'girl_group' => 0, 'co_ed' => 0, 'unknown' => 0], $statistics['byLanguage']['en']['groupTypes']);
    }
}
