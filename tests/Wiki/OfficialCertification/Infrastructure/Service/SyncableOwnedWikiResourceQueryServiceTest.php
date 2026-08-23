<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Infrastructure\Service;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Application\Service\SyncableOwnedWikiResource;
use Source\Wiki\OfficialCertification\Infrastructure\Service\SyncableOwnedWikiResourceQueryService;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SyncableOwnedWikiResourceQueryServiceTest extends TestCase
{
    #[Group('useDb')]
    public function testFindSyncableResourcesReturnsPublishedGroupAndSongRelatedToOwnedAgency(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $ownedAgencyWikiId = StrTestHelper::generateUuid();
        $otherAgencyWikiId = StrTestHelper::generateUuid();
        $groupTranslationSetIdentifier = StrTestHelper::generateUuid();
        $songTranslationSetIdentifier = StrTestHelper::generateUuid();
        $this->createWiki($ownedAgencyWikiId, ResourceType::AGENCY, [
            'owner_account_id' => (string) $accountIdentifier,
        ]);
        $this->createWiki($otherAgencyWikiId, ResourceType::AGENCY, [
            'owner_account_id' => StrTestHelper::generateUuid(),
        ]);
        $this->createWiki(StrTestHelper::generateUuid(), ResourceType::GROUP, [
            'translation_set_identifier' => $groupTranslationSetIdentifier,
            'published_at' => '2026-01-01 00:00:00',
        ], [
            'agency_identifier' => $ownedAgencyWikiId,
        ]);
        $this->createWiki(StrTestHelper::generateUuid(), ResourceType::GROUP, [
            'translation_set_identifier' => $groupTranslationSetIdentifier,
            'language' => 'ja',
            'published_at' => '2026-01-02 00:00:00',
        ], [
            'agency_identifier' => $ownedAgencyWikiId,
        ]);
        $this->createWiki(StrTestHelper::generateUuid(), ResourceType::SONG, [
            'translation_set_identifier' => $songTranslationSetIdentifier,
            'published_at' => '2026-01-03 00:00:00',
        ], [
            'agency_identifier' => $ownedAgencyWikiId,
        ]);
        $this->createWiki(StrTestHelper::generateUuid(), ResourceType::GROUP, [
            'published_at' => null,
        ], [
            'agency_identifier' => $ownedAgencyWikiId,
        ]);
        $this->createWiki(StrTestHelper::generateUuid(), ResourceType::GROUP, [
            'published_at' => '2026-01-04 00:00:00',
        ], [
            'agency_identifier' => $otherAgencyWikiId,
        ]);

        $resources = (new SyncableOwnedWikiResourceQueryService())->findSyncableResources($accountIdentifier);

        $this->assertSame([
            ResourceType::GROUP->value . ':' . $groupTranslationSetIdentifier,
            ResourceType::SONG->value . ':' . $songTranslationSetIdentifier,
        ], $this->resourceKeys($resources));
    }

    #[Group('useDb')]
    public function testFindSyncableResourcesReturnsEmptyWhenAccountHasNoAgencyWiki(): void
    {
        $this->createWiki(StrTestHelper::generateUuid(), ResourceType::GROUP, [
            'published_at' => '2026-01-01 00:00:00',
        ], [
            'agency_identifier' => StrTestHelper::generateUuid(),
        ]);

        $resources = (new SyncableOwnedWikiResourceQueryService())->findSyncableResources(
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertSame([], $resources);
    }

    #[Group('useDb')]
    public function testFindOfficialResourcesReturnsOnlyProvidedResourcesOwnedByAccount(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $groupTranslationSetIdentifier = StrTestHelper::generateUuid();
        $songTranslationSetIdentifier = StrTestHelper::generateUuid();
        $otherOwnerTranslationSetIdentifier = StrTestHelper::generateUuid();
        $missingTranslationSetIdentifier = StrTestHelper::generateUuid();
        $this->createWiki(StrTestHelper::generateUuid(), ResourceType::GROUP, [
            'translation_set_identifier' => $groupTranslationSetIdentifier,
            'owner_account_id' => (string) $accountIdentifier,
        ]);
        $this->createWiki(StrTestHelper::generateUuid(), ResourceType::GROUP, [
            'translation_set_identifier' => $groupTranslationSetIdentifier,
            'language' => 'ja',
            'owner_account_id' => (string) $accountIdentifier,
        ]);
        $this->createWiki(StrTestHelper::generateUuid(), ResourceType::SONG, [
            'translation_set_identifier' => $songTranslationSetIdentifier,
            'owner_account_id' => (string) $accountIdentifier,
        ]);
        $this->createWiki(StrTestHelper::generateUuid(), ResourceType::GROUP, [
            'translation_set_identifier' => $otherOwnerTranslationSetIdentifier,
            'owner_account_id' => StrTestHelper::generateUuid(),
        ]);

        $resources = (new SyncableOwnedWikiResourceQueryService())->findOfficialResources(
            $accountIdentifier,
            [
                new SyncableOwnedWikiResource(ResourceType::GROUP, new TranslationSetIdentifier($groupTranslationSetIdentifier)),
                new SyncableOwnedWikiResource(ResourceType::SONG, new TranslationSetIdentifier($songTranslationSetIdentifier)),
                new SyncableOwnedWikiResource(ResourceType::GROUP, new TranslationSetIdentifier($otherOwnerTranslationSetIdentifier)),
                new SyncableOwnedWikiResource(ResourceType::GROUP, new TranslationSetIdentifier($missingTranslationSetIdentifier)),
            ],
        );

        $this->assertSame([
            ResourceType::GROUP->value . ':' . $groupTranslationSetIdentifier,
            ResourceType::SONG->value . ':' . $songTranslationSetIdentifier,
        ], $this->resourceKeys($resources));
    }

    public function testFindOfficialResourcesReturnsEmptyWhenResourcesAreEmpty(): void
    {
        $resources = (new SyncableOwnedWikiResourceQueryService())->findOfficialResources(
            new AccountIdentifier(StrTestHelper::generateUuid()),
            [],
        );

        $this->assertSame([], $resources);
    }

    /**
     * @param array<string, mixed> $overrides
     * @param array<string, mixed> $basicOverrides
     */
    private function createWiki(
        string $wikiId,
        ResourceType $resourceType,
        array $overrides = [],
        array $basicOverrides = [],
    ): void {
        CreateWiki::create($wikiId, $resourceType->value, [
            'slug' => $resourceType->value . '-' . StrTestHelper::generateUuid(),
            ...$overrides,
        ], $basicOverrides);
    }

    /**
     * @param SyncableOwnedWikiResource[] $resources
     * @return string[]
     */
    private function resourceKeys(array $resources): array
    {
        $keys = array_map(
            static fn (SyncableOwnedWikiResource $resource): string => $resource->key(),
            $resources,
        );
        sort($keys);

        return $keys;
    }
}
