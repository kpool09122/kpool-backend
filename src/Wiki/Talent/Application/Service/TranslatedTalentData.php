<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\Service;

final readonly class TranslatedTalentData
{
    public function __construct(
        private string $translatedName,
        private string $translatedRealName,
        private string $translatedCareer,
    ) {
    }

    public function translatedName(): string
    {
        return $this->translatedName;
    }

    public function translatedRealName(): string
    {
        return $this->translatedRealName;
    }

    public function translatedCareer(): string
    {
        return $this->translatedCareer;
    }
}
