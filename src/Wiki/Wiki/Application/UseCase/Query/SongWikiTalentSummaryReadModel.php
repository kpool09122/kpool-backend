<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class SongWikiTalentSummaryReadModel
{
    public function __construct(
        private string $wikiIdentifier,
        private string $slug,
        private string $language,
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
        private ?string $profileImageIdentifier,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'wikiIdentifier' => $this->wikiIdentifier,
            'slug' => $this->slug,
            'language' => $this->language,
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
            'profileImageIdentifier' => $this->profileImageIdentifier,
        ];
    }
}
