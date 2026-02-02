<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent;

use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

final readonly class TalentBasic implements BasicInterface
{
    /**
     * @param array<WikiIdentifier> $groupIdentifiers
     */
    public function __construct(
        private Name $name,
        private string $normalizedName,
        private RealName $realName,
        private string $normalizedRealName,
        private ?Birthday $birthday,
        private ?WikiIdentifier $agencyIdentifier,
        private array $groupIdentifiers,
        private Emoji $emoji,
        private RepresentativeSymbol $representativeSymbol,
        private Position $position,
        private ?MBTI $mbti,
        private ?ZodiacSign $zodiacSign,
        private ?EnglishLevel $englishLevel,
        private ?Height $height,
        private ?BloodType $bloodType,
        private FandomName $fandomName,
        private ?ImageIdentifier $profileImageIdentifier,
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

    public function realName(): RealName
    {
        return $this->realName;
    }

    public function normalizedRealName(): string
    {
        return $this->normalizedRealName;
    }

    public function birthday(): ?Birthday
    {
        return $this->birthday;
    }

    public function agencyIdentifier(): ?WikiIdentifier
    {
        return $this->agencyIdentifier;
    }

    /**
     * @return array<WikiIdentifier>
     */
    public function groupIdentifiers(): array
    {
        return $this->groupIdentifiers;
    }

    public function emoji(): Emoji
    {
        return $this->emoji;
    }

    public function representativeSymbol(): RepresentativeSymbol
    {
        return $this->representativeSymbol;
    }

    public function position(): Position
    {
        return $this->position;
    }

    public function mbti(): ?MBTI
    {
        return $this->mbti;
    }

    public function zodiacSign(): ?ZodiacSign
    {
        return $this->zodiacSign;
    }

    public function englishLevel(): ?EnglishLevel
    {
        return $this->englishLevel;
    }

    public function height(): ?Height
    {
        return $this->height;
    }

    public function bloodType(): ?BloodType
    {
        return $this->bloodType;
    }

    public function fandomName(): FandomName
    {
        return $this->fandomName;
    }

    public function profileImageIdentifier(): ?ImageIdentifier
    {
        return $this->profileImageIdentifier;
    }

    public function supportsResourceType(ResourceType $resourceType): bool
    {
        return $resourceType === ResourceType::TALENT;
    }

    public function getBasicType(): string
    {
        return 'talent';
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
            'real_name' => (string) $this->realName,
            'normalized_real_name' => $this->normalizedRealName,
            'birthday' => $this->birthday,
            'agency_identifier' => $this->agencyIdentifier !== null ? (string)$this->agencyIdentifier : null,
            'group_identifiers' => array_map(static fn ($groupIdentifier) => (string) $groupIdentifier, $this->groupIdentifiers),
            'emoji' => $this->emoji->value(),
            'representative_symbol' => $this->representativeSymbol->value(),
            'position' => $this->position->value(),
            'mbti' => $this->mbti?->value,
            'zodiac_sign' => $this->zodiacSign?->value,
            'english_level' => $this->englishLevel?->value,
            'height' => $this->height?->centimeters(),
            'blood_type' => $this->bloodType?->value,
            'fandom_name' => $this->fandomName->value(),
            'profile_image_identifier' => $this->profileImageIdentifier !== null ? (string)$this->profileImageIdentifier : null,
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
            realName: new RealName($data['real_name'] ?? ''),
            normalizedRealName: $data['normalized_real_name'] ?? '',
            birthday: $data['birthday'] ?? null,
            agencyIdentifier: $data['agency_identifier'] ? new WikiIdentifier($data['agency_identifier']) : null,
            groupIdentifiers: $data['group_identifiers'] ? array_map(static fn ($groupIdentifier) => new WikiIdentifier($groupIdentifier), $data['group_identifiers']) : [],
            emoji: new Emoji($data['emoji'] ?? ''),
            representativeSymbol: new RepresentativeSymbol($data['representative_symbol'] ?? ''),
            position: new Position($data['position'] ?? ''),
            mbti: isset($data['mbti']) ? MBTI::from($data['mbti']) : null,
            zodiacSign: isset($data['zodiac_sign']) ? ZodiacSign::from($data['zodiac_sign']) : null,
            englishLevel: isset($data['english_level']) ? EnglishLevel::from($data['english_level']) : null,
            height: isset($data['height']) ? new Height((int) $data['height']) : null,
            bloodType: isset($data['blood_type']) ? BloodType::from($data['blood_type']) : null,
            fandomName: new FandomName($data['fandom_name'] ?? ''),
            profileImageIdentifier: isset($data['profile_image_identifier']) ? new ImageIdentifier($data['profile_image_identifier']) : null,
        );
    }
}
