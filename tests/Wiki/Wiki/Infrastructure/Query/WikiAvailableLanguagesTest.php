<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyWiki\GetAgencyWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyWiki\GetAgencyWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupWiki\GetGroupWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupWiki\GetGroupWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\GetSongWiki\GetSongWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetSongWiki\GetSongWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentWiki\GetTalentWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentWiki\GetTalentWikiInterface;
use Tests\Helper\CreateDraftWiki;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WikiAvailableLanguagesTest extends TestCase
{
    /** @return iterable<string, array{ResourceType}> */
    public static function resourceTypes(): iterable
    {
        foreach ([ResourceType::AGENCY, ResourceType::GROUP, ResourceType::TALENT, ResourceType::SONG] as $resourceType) {
            yield $resourceType->value => [$resourceType];
        }
    }

    #[Group('useDb')]
    #[DataProvider('resourceTypes')]
    public function testDetailsExposeOnlyTheirPublishedTranslationLanguages(ResourceType $resourceType): void
    {
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $slug = new Slug($resourceType->slugPrefix() . '-translation-test');
        foreach (['ko', 'en'] as $language) {
            CreateWiki::create(StrTestHelper::generateUuid(), $resourceType->value, [
                'translation_set_identifier' => $translationSetIdentifier,
                'slug' => (string) $slug,
                'language' => $language,
            ]);
        }
        CreateDraftWiki::create(StrTestHelper::generateUuid(), $resourceType->value, [
            'translation_set_identifier' => $translationSetIdentifier,
            'slug' => (string) $slug,
            'language' => 'ja',
        ]);
        CreateWiki::create(StrTestHelper::generateUuid(), $resourceType->value, [
            'language' => 'ja',
        ]);
        $otherType = $resourceType === ResourceType::AGENCY ? ResourceType::GROUP : ResourceType::AGENCY;
        CreateWiki::create(StrTestHelper::generateUuid(), $otherType->value, [
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => 'ja',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $model = WikiModel::query()
            ->withAvailableLanguages()
            ->where('resource_type', $resourceType->value)
            ->where('slug', (string) $slug)
            ->where('language', 'ko')
            ->firstOrFail();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(1, $queries, 'Available languages must be loaded with the wiki itself.');
        $this->assertSame(['en', 'ko'], $model->available_languages);

        $readModel = match ($resourceType) {
            ResourceType::AGENCY => $this->app()->make(GetAgencyWikiInterface::class)->process(new GetAgencyWikiInput($slug, Language::KOREAN)),
            ResourceType::GROUP => $this->app()->make(GetGroupWikiInterface::class)->process(new GetGroupWikiInput($slug, Language::KOREAN)),
            ResourceType::TALENT => $this->app()->make(GetTalentWikiInterface::class)->process(new GetTalentWikiInput($slug, Language::KOREAN)),
            ResourceType::SONG => $this->app()->make(GetSongWikiInterface::class)->process(new GetSongWikiInput($slug, Language::KOREAN)),
            default => $this->fail('Unsupported wiki resource type.'),
        };
        $this->assertSame(['en', 'ko'], $readModel->availableLanguages());
        $this->assertSame(['en', 'ko'], $readModel->toArray()['availableLanguages']);

        WikiModel::query()
            ->where('translation_set_identifier', $translationSetIdentifier)
            ->where('resource_type', $resourceType->value)
            ->where('language', 'en')
            ->delete();
        $remaining = match ($resourceType) {
            ResourceType::AGENCY => $this->app()->make(GetAgencyWikiInterface::class)->process(new GetAgencyWikiInput($slug, Language::KOREAN)),
            ResourceType::GROUP => $this->app()->make(GetGroupWikiInterface::class)->process(new GetGroupWikiInput($slug, Language::KOREAN)),
            ResourceType::TALENT => $this->app()->make(GetTalentWikiInterface::class)->process(new GetTalentWikiInput($slug, Language::KOREAN)),
            ResourceType::SONG => $this->app()->make(GetSongWikiInterface::class)->process(new GetSongWikiInput($slug, Language::KOREAN)),
        };
        $this->assertSame(['ko'], $remaining->availableLanguages());
    }
}
