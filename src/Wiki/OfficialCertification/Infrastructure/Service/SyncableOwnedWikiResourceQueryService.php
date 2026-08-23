<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Infrastructure\Service;

use Application\Models\Wiki\Wiki as WikiModel;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Application\Service\SyncableOwnedWikiResource;
use Source\Wiki\OfficialCertification\Application\Service\SyncableOwnedWikiResourceQueryServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class SyncableOwnedWikiResourceQueryService implements SyncableOwnedWikiResourceQueryServiceInterface
{
    /** @return SyncableOwnedWikiResource[] */
    public function findSyncableResources(AccountIdentifier $accountIdentifier): array
    {
        $ownedAgencyWikiIds = WikiModel::query()
            ->where('owner_account_id', (string) $accountIdentifier)
            ->where('resource_type', ResourceType::AGENCY->value)
            ->pluck('id')
            ->all();

        if ($ownedAgencyWikiIds === []) {
            return [];
        }

        $groupResources = WikiModel::query()
            ->select('wikis.resource_type', 'wikis.translation_set_identifier')
            ->join('wiki_group_basics', 'wiki_group_basics.wiki_id', '=', 'wikis.id')
            ->where('wikis.resource_type', ResourceType::GROUP->value)
            ->whereNotNull('wikis.published_at')
            ->whereIn('wiki_group_basics.agency_identifier', $ownedAgencyWikiIds)
            ->distinct()
            ->get();

        $songResources = WikiModel::query()
            ->select('wikis.resource_type', 'wikis.translation_set_identifier')
            ->join('wiki_song_basics', 'wiki_song_basics.wiki_id', '=', 'wikis.id')
            ->where('wikis.resource_type', ResourceType::SONG->value)
            ->whereNotNull('wikis.published_at')
            ->whereIn('wiki_song_basics.agency_identifier', $ownedAgencyWikiIds)
            ->distinct()
            ->get();

        return $groupResources
            ->toBase()
            ->concat($songResources->toBase())
            ->map(static fn (WikiModel $wiki): SyncableOwnedWikiResource => self::toResource($wiki))
            ->unique(static fn (SyncableOwnedWikiResource $resource): string => $resource->key())
            ->values()
            ->all();
    }

    /**
     * @param SyncableOwnedWikiResource[] $resources
     * @return SyncableOwnedWikiResource[]
     */
    public function findOfficialResources(AccountIdentifier $accountIdentifier, array $resources): array
    {
        if ($resources === []) {
            return [];
        }

        return WikiModel::query()
            ->select('resource_type', 'translation_set_identifier')
            ->where('owner_account_id', (string) $accountIdentifier)
            ->where(static function ($query) use ($resources): void {
                foreach ($resources as $resource) {
                    $query->orWhere(static function ($query) use ($resource): void {
                        $query->where('resource_type', $resource->resourceType()->value)
                            ->where('translation_set_identifier', (string) $resource->translationSetIdentifier());
                    });
                }
            })
            ->distinct()
            ->get()
            ->map(static fn (WikiModel $wiki): SyncableOwnedWikiResource => self::toResource($wiki))
            ->unique(static fn (SyncableOwnedWikiResource $resource): string => $resource->key())
            ->values()
            ->all();
    }

    private static function toResource(WikiModel $wiki): SyncableOwnedWikiResource
    {
        return new SyncableOwnedWikiResource(
            ResourceType::from($wiki->resource_type),
            new TranslationSetIdentifier($wiki->translation_set_identifier),
        );
    }
}
