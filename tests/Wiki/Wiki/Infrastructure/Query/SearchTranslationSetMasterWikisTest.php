<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis\SearchTranslationSetMasterWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis\SearchTranslationSetMasterWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis\SearchTranslationSetMasterWikisOutput;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SearchTranslationSetMasterWikisTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessGroupsMatchedTranslationSetAndReturnsAllLanguages(): void
    {
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f219a001', $translationSetIdentifier, 'ko', 'gr-ive-ko', 'IVE KO', 'ive');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f219a002', $translationSetIdentifier, 'ja', 'gr-ive-ja', 'IVE JA', 'ive');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f219a003', StrTestHelper::generateUuid(), 'ko', 'gr-lesserafim-ko', 'LE SSERAFIM', 'le sserafim');

        $payload = $this->process(ResourceType::GROUP, 'ive')->toArray();

        $this->assertCount(1, $payload['translationSetMasters']);
        $this->assertSame($translationSetIdentifier, $payload['translationSetMasters'][0]['translationSetIdentifier']);
        $this->assertSame('group', $payload['translationSetMasters'][0]['resourceType']);
        $this->assertSame(['ja', 'ko'], array_column($payload['translationSetMasters'][0]['wikis'], 'language'));
        $this->assertSame(['IVE JA', 'IVE KO'], array_column($payload['translationSetMasters'][0]['wikis'], 'name'));
    }

    #[Group('useDb')]
    public function testProcessAppliesLimitToTranslationSets(): void
    {
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f219b001', StrTestHelper::generateUuid(), 'ko', 'ag-alpha', 'Alpha Agency', 'alpha agency', 'agency');
        $this->createWiki('01965bb2-bcc9-7c6f-8b90-89f7f219b002', StrTestHelper::generateUuid(), 'ko', 'ag-beta', 'Beta Agency', 'beta agency', 'agency');

        $payload = $this->process(ResourceType::AGENCY, 'agency', 1)->toArray();

        $this->assertCount(1, $payload['translationSetMasters']);
        $this->assertSame(['translationSetIdentifier', 'resourceType', 'wikis'], array_keys($payload['translationSetMasters'][0]));
        $this->assertSame(['wikiIdentifier', 'language', 'name', 'slug'], array_keys($payload['translationSetMasters'][0]['wikis'][0]));
    }

    private function process(ResourceType $resourceType, string $keyword, ?int $limit = null): SearchTranslationSetMasterWikisOutput
    {
        $output = new SearchTranslationSetMasterWikisOutput();
        $this->app->make(SearchTranslationSetMasterWikisInterface::class)->process(
            new SearchTranslationSetMasterWikisInput($resourceType, $keyword, $limit),
            $output,
        );

        return $output;
    }

    private function createWiki(
        string $wikiId,
        string $translationSetIdentifier,
        string $language,
        string $slug,
        string $name,
        string $normalizedName,
        string $resourceType = 'group',
    ): void {
        CreateWiki::create(
            $wikiId,
            $resourceType,
            [
                'translation_set_identifier' => $translationSetIdentifier,
                'slug' => $slug,
                'language' => $language,
                'published_at' => '2026-04-01 00:00:00',
            ],
            [
                'name' => $name,
                'normalized_name' => $normalizedName,
            ],
        );
    }
}
