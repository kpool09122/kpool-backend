<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\Service;

final readonly class TranslatedSongData
{
    public function __construct(
        private string $translatedName,
        private string $translatedLyricist,
        private string $translatedComposer,
        private string $translatedOverview,
    ) {
    }

    public function translatedName(): string
    {
        return $this->translatedName;
    }

    public function translatedLyricist(): string
    {
        return $this->translatedLyricist;
    }

    public function translatedComposer(): string
    {
        return $this->translatedComposer;
    }

    public function translatedOverview(): string
    {
        return $this->translatedOverview;
    }
}
