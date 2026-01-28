<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\Service;

final readonly class TranslatedAgencyData
{
    public function __construct(
        private string $translatedName,
        private string $translatedCEO,
        private string $translatedDescription,
    ) {
    }

    public function translatedName(): string
    {
        return $this->translatedName;
    }

    public function translatedCEO(): string
    {
        return $this->translatedCEO;
    }

    public function translatedDescription(): string
    {
        return $this->translatedDescription;
    }
}
