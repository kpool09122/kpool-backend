<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Query;

use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;

readonly class OfficialCertificationListItemReadModel
{
    public function __construct(
        private string $certificationIdentifier,
        private string $resourceType,
        private string $translationSetIdentifier,
        private ?OfficialCertificationOwnerAccountReadModel $ownerAccount,
        /** @var list<WikiListItemReadModel> */
        private array $wikis,
        private string $status,
        private string $requestedAt,
        private ?string $approvedAt,
        private ?string $rejectedAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'certificationIdentifier' => $this->certificationIdentifier,
            'resourceType' => $this->resourceType,
            'translationSetIdentifier' => $this->translationSetIdentifier,
            'ownerAccount' => $this->ownerAccount?->toArray(),
            'wikis' => array_map(
                static fn (WikiListItemReadModel $wiki): array => $wiki->toArray(),
                $this->wikis,
            ),
            'status' => $this->status,
            'requestedAt' => $this->requestedAt,
            'approvedAt' => $this->approvedAt,
            'rejectedAt' => $this->rejectedAt,
        ];
    }
}
