<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications;

use Source\Wiki\OfficialCertification\Application\Service\SyncableOwnedWikiResource;

class SyncOwnedWikiCertificationsOutput implements SyncOwnedWikiCertificationsOutputPort
{
    /** @var SyncableOwnedWikiResource[] */
    private array $approved = [];

    /** @var SyncableOwnedWikiResource[] */
    private array $rejected = [];

    /** @var SyncableOwnedWikiResource[] */
    private array $unchanged = [];

    /**
     * @param SyncableOwnedWikiResource[] $approved
     * @param SyncableOwnedWikiResource[] $rejected
     * @param SyncableOwnedWikiResource[] $unchanged
     */
    public function setResult(array $approved, array $rejected, array $unchanged): void
    {
        $this->approved = $approved;
        $this->rejected = $rejected;
        $this->unchanged = $unchanged;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'approved' => $this->resourcesToArray($this->approved),
            'rejected' => $this->resourcesToArray($this->rejected),
            'unchanged' => $this->resourcesToArray($this->unchanged),
        ];
    }

    /**
     * @param SyncableOwnedWikiResource[] $resources
     * @return array<int, array{resourceType: string, translationSetIdentifier: string}>
     */
    private function resourcesToArray(array $resources): array
    {
        return array_map(
            static fn (SyncableOwnedWikiResource $resource): array => $resource->toArray(),
            $resources,
        );
    }
}
