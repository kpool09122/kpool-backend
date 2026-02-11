<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

final readonly class AgencyBasic implements BasicInterface
{
    /**
     * @param array<ExternalContentLink> $socialLinks
     */
    public function __construct(
        private Name $name,
        private string $normalizedName,
        private CEO $ceo,
        private string $normalizedCeo,
        private ?FoundedIn $foundedIn,
        private ?WikiIdentifier $parentAgencyIdentifier,
        private ?AgencyStatus $status,
        private ?ImageIdentifier $logoImageIdentifier,
        private ?ExternalContentLink $officialWebsite,
        private array $socialLinks,
    ) {
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function normalizedName(): string
    {
        return $this->normalizedName;
    }

    public function ceo(): CEO
    {
        return $this->ceo;
    }

    public function normalizedCeo(): string
    {
        return $this->normalizedCeo;
    }

    public function foundedIn(): ?FoundedIn
    {
        return $this->foundedIn;
    }

    public function parentAgencyIdentifier(): ?WikiIdentifier
    {
        return $this->parentAgencyIdentifier;
    }

    public function status(): ?AgencyStatus
    {
        return $this->status;
    }

    public function logoImageIdentifier(): ?ImageIdentifier
    {
        return $this->logoImageIdentifier;
    }

    public function officialWebsite(): ?ExternalContentLink
    {
        return $this->officialWebsite;
    }

    /**
     * @return array<ExternalContentLink>
     */
    public function socialLinks(): array
    {
        return $this->socialLinks;
    }

    public function normalizableKeys(): array
    {
        return [
            'name' => 'normalized_name',
            'ceo' => 'normalized_ceo',
        ];
    }

    public function supportsResourceType(ResourceType $resourceType): bool
    {
        return $resourceType === ResourceType::AGENCY;
    }

    public function getBasicType(): string
    {
        return 'agency';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getBasicType(),
            'name' => (string)$this->name,
            'normalized_name' => $this->normalizedName,
            'ceo' => (string)$this->ceo,
            'normalized_ceo' => $this->normalizedCeo,
            'founded_in' => $this->foundedIn?->format('Y-m-d'),
            'parent_agency_identifier' => $this->parentAgencyIdentifier !== null ? (string)$this->parentAgencyIdentifier : null,
            'status' => $this->status?->value,
            'logo_image_identifier' => $this->logoImageIdentifier !== null ? (string)$this->logoImageIdentifier : null,
            'official_website' => $this->officialWebsite !== null ? (string)$this->officialWebsite : null,
            'social_links' => array_map(
                static fn (ExternalContentLink $link) => (string)$link,
                $this->socialLinks
            ),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: new Name($data['name']),
            normalizedName: $data['normalized_name'] ?? '',
            ceo: new CEO($data['ceo'] ?? ''),
            normalizedCeo: $data['normalized_ceo'] ?? '',
            foundedIn: isset($data['founded_in']) ? new FoundedIn(new DateTimeImmutable($data['founded_in'])) : null,
            parentAgencyIdentifier: isset($data['parent_agency_identifier']) ? new WikiIdentifier($data['parent_agency_identifier']) : null,
            status: isset($data['status']) ? AgencyStatus::from($data['status']) : null,
            logoImageIdentifier: isset($data['logo_image_identifier']) ? new ImageIdentifier($data['logo_image_identifier']) : null,
            officialWebsite: isset($data['official_website']) ? new ExternalContentLink($data['official_website']) : null,
            socialLinks: isset($data['social_links'])
                ? array_map(static fn (string $link) => new ExternalContentLink($link), $data['social_links'])
                : [],
        );
    }
}
