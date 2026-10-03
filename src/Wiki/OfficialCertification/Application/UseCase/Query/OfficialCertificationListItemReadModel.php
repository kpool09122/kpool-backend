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
     * @return array{certificationIdentifier: string, resourceType: string, translationSetIdentifier: string, ownerAccount: array{accountIdentifier: string, email: string, type: string, name: string, status: string, category: string}|null, wikis: list<array{wikiIdentifier: string, translationSetIdentifier: string, slug: string, language: string, resourceType: string, version: int, isOfficial: bool, themeColor: string|null, fontStyle: string|null, title: string|null, metaDescription: string|null, keywords: list<string>|null, imageIdentifier: string|null, imageUrl: string|null, imageAltText: string|null, isHidden: bool|null, name: string, normalizedName: string, publishedAt: string|null, updatedAt: string|null}>, status: string, requestedAt: string, approvedAt: string|null, rejectedAt: string|null}
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
