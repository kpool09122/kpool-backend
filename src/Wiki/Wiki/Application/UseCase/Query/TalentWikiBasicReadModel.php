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
        private ?WikiAgencySummaryReadModel $agency,
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
     * @return array{name: string, normalizedName: string, realName: string, normalizedRealName: string, birthday: string|null, agencyIdentifier: string|null, agency: array{wikiIdentifier: string, slug: string, language: string, name: string, normalizedName: string}|null, emoji: string, representativeSymbol: string, position: string, mbti: string|null, zodiacSign: string|null, englishLevel: string|null, height: int|string|null, bloodType: string|null, fandomName: string, groups: list<array{wikiIdentifier: string, slug: string, language: string, name: string, normalizedName: string, agencyIdentifier: string|null, groupType: string|null, status: string|null, generation: string|null, debutDate: string|null, disbandDate: string|null, fandomName: string, officialColors: list<array{colorCode: string, label: string}>, emoji: string, representativeSymbol: string}>}
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
            'agency' => $this->agency?->toArray(),
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
