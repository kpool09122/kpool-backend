<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Group;

use DateTimeImmutable;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

final readonly class GroupBasic implements BasicInterface
{
    /**
     * @param array<Color> $officialColors
     */
    public function __construct(
        private Name $name,
        private string $normalizedName,
        private ?WikiIdentifier $agencyIdentifier,
        private ?GroupType $groupType,
        private ?GroupStatus $status,
        private ?Generation $generation,
        private ?DebutDate $debutDate,
        private ?DisbandDate $disbandDate,
        private FandomName $fandomName,
        private array $officialColors,
        private Emoji $emoji,
        private RepresentativeSymbol $representativeSymbol,
        private ?ImageIdentifier $mainImageIdentifier,
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

    public function agencyIdentifier(): ?WikiIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function groupType(): ?GroupType
    {
        return $this->groupType;
    }

    public function status(): ?GroupStatus
    {
        return $this->status;
    }

    public function generation(): ?Generation
    {
        return $this->generation;
    }

    public function debutDate(): ?DebutDate
    {
        return $this->debutDate;
    }

    public function disbandDate(): ?DisbandDate
    {
        return $this->disbandDate;
    }

    public function fandomName(): FandomName
    {
        return $this->fandomName;
    }

    /**
     * @return array<Color>
     */
    public function officialColors(): array
    {
        return $this->officialColors;
    }

    public function emoji(): Emoji
    {
        return $this->emoji;
    }

    public function representativeSymbol(): RepresentativeSymbol
    {
        return $this->representativeSymbol;
    }

    public function mainImageIdentifier(): ?ImageIdentifier
    {
        return $this->mainImageIdentifier;
    }

    public function supportsResourceType(ResourceType $resourceType): bool
    {
        return $resourceType === ResourceType::GROUP;
    }

    public function getBasicType(): string
    {
        return 'group';
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
            'agency_identifier' => $this->agencyIdentifier !== null ? (string)$this->agencyIdentifier : null,
            'group_type' => $this->groupType?->value,
            'status' => $this->status?->value,
            'generation' => $this->generation?->value,
            'debut_date' => $this->debutDate?->format('Y-m-d'),
            'disband_date' => $this->disbandDate?->format('Y-m-d'),
            'fandom_name' => $this->fandomName->value(),
            'official_colors' => array_map(
                static fn (Color $color) => (string) $color,
                $this->officialColors
            ),
            'emoji' => $this->emoji->value(),
            'representative_symbol' => $this->representativeSymbol->value(),
            'main_image_identifier' => $this->mainImageIdentifier !== null ? (string)$this->mainImageIdentifier : null,
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
            agencyIdentifier: $data['agency_identifier'] ? new WikiIdentifier($data['agency_identifier']) : null,
            groupType: isset($data['group_type']) ? GroupType::from($data['group_type']) : null,
            status: isset($data['status']) ? GroupStatus::from($data['status']) : null,
            generation: isset($data['generation']) ? Generation::from($data['generation']) : null,
            debutDate: isset($data['debut_date']) ? new DebutDate(new DateTimeImmutable($data['debut_date'])) : null,
            disbandDate: isset($data['disband_date']) ? new DisbandDate(new DateTimeImmutable($data['disband_date'])) : null,
            fandomName: new FandomName($data['fandom_name'] ?? ''),
            officialColors: isset($data['official_colors'])
                ? array_map(static fn (string $color) => new Color($color), $data['official_colors'])
                : [],
            emoji: new Emoji($data['emoji'] ?? ''),
            representativeSymbol: new RepresentativeSymbol($data['representative_symbol'] ?? ''),
            mainImageIdentifier: isset($data['main_image_identifier']) ? new ImageIdentifier($data['main_image_identifier']) : null,
        );
    }
}
