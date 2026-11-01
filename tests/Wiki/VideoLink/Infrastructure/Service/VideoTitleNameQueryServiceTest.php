<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Wiki\VideoLink\Application\Service\VideoTitleNameQueryServiceInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class VideoTitleNameQueryServiceTest extends TestCase
{
    #[DataProvider('resourceTypes')]
    public function testFindsNamesAndDirectRelatedNamesAcrossAllLanguages(string $resourceType): void
    {
        $ids = ['group' => $this->wikiIds(), 'talent' => $this->wikiIds(), 'song' => $this->wikiIds()];
        foreach (['group', 'talent', 'song'] as $type) {
            $translationSet = StrTestHelper::generateUuid();
            foreach (['ko', 'ja', 'en'] as $language) {
                $basic = ['name' => $type . '-' . $language];
                // Only the Japanese profiles carry relations; English profiles have no links.
                if ($type !== 'group' && $language === 'ja') {
                    $basic['group_identifiers'] = json_encode([$ids['group']['ja']], JSON_THROW_ON_ERROR);
                }
                if ($type === 'song' && $language === 'ja') {
                    $basic['talent_identifiers'] = json_encode([$ids['talent']['ja']], JSON_THROW_ON_ERROR);
                }
                CreateWiki::create($ids[$type][$language], $type, [
                    'translation_set_identifier' => $translationSet,
                    'language' => $language,
                ], $basic);
            }
        }
        CreateWiki::create(StrTestHelper::generateUuid(), 'talent', ['slug' => 'tl-unrelated'], ['name' => 'Unrelated']);
        $wiki = $this->app()->make(WikiRepositoryInterface::class)->findById(new WikiIdentifier($ids[$resourceType]['ko']));
        self::assertNotNull($wiki);
        DB::enableQueryLog();
        DB::flushQueryLog();

        try {
            $names = $this->app()->make(VideoTitleNameQueryServiceInterface::class)->findNames($wiki);
            self::assertLessThanOrEqual(6, count(DB::getQueryLog()));
        } finally {
            DB::disableQueryLog();
        }

        foreach (['ko', 'ja', 'en'] as $language) {
            self::assertEqualsCanonicalizing(['group-' . $language, 'talent-' . $language, 'song-' . $language], $names[$language]);
        }
    }

    public function testDoesNotTraverseRelationsRecursively(): void
    {
        $group = StrTestHelper::generateUuid();
        $talent = StrTestHelper::generateUuid();
        CreateWiki::create($group, 'group', [], ['name' => 'Group']);
        CreateWiki::create($talent, 'talent', [], ['name' => 'Member', 'group_identifiers' => json_encode([$group], JSON_THROW_ON_ERROR)]);
        CreateWiki::create(StrTestHelper::generateUuid(), 'song', [], ['name' => 'Solo song', 'talent_identifiers' => json_encode([$talent], JSON_THROW_ON_ERROR)]);
        $wiki = $this->app()->make(WikiRepositoryInterface::class)->findById(new WikiIdentifier($group));
        self::assertNotNull($wiki);

        $names = $this->app()->make(VideoTitleNameQueryServiceInterface::class)->findNames($wiki);

        self::assertEqualsCanonicalizing(['Group', 'Member'], $names['ko']);
    }

    /** @return array{ko: string, ja: string, en: string} */
    private function wikiIds(): array
    {
        return ['ko' => StrTestHelper::generateUuid(), 'ja' => StrTestHelper::generateUuid(), 'en' => StrTestHelper::generateUuid()];
    }

    /** @return array<string, array{string}> */
    public static function resourceTypes(): array
    {
        return ['group' => ['group'], 'talent' => ['talent'], 'song' => ['song']];
    }
}
