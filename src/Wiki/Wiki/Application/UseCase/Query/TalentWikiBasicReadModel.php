<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class TalentWikiBasicReadModel implements WikiBasicReadModel
{
    use ArrayAccessibleReadModel;

    /**
     * @param list<TalentWikiGroupSummaryReadModel> $groups
     */
    public function __construct(
        private string $name,
        private string $normalizedName,
        private string $realName,
        private string $normalizedRealName,
        private ?string $birthday,
        private ?string $agencyIdentifier,
        private string $emoji,
        private string $representativeSymbol,
        private string $position,
        private ?string $mbti,
        private ?string $zodiacSign,
        private ?string $englishLevel,
        private int|string|null $height,
        private ?string $bloodType,
        private string $fandomName,
        private array $groups,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'normalizedName' => $this->normalizedName,
            'realName' => $this->realName,
            'normalizedRealName' => $this->normalizedRealName,
            'birthday' => $this->birthday,
            'agencyIdentifier' => $this->agencyIdentifier,
            'emoji' => $this->emoji,
            'representativeSymbol' => $this->representativeSymbol,
            'position' => $this->position,
            'mbti' => $this->mbti,
            'zodiacSign' => $this->zodiacSign,
            'englishLevel' => $this->englishLevel,
            'height' => $this->height,
            'bloodType' => $this->bloodType,
            'fandomName' => $this->fandomName,
            'groups' => array_map(
                static fn (TalentWikiGroupSummaryReadModel $group): array => $group->toArray(),
                $this->groups,
            ),
        ];
    }
}
