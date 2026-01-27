<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\Service;

final readonly class TranslatedGroupData
{
    public function __construct(
        private string $translatedName,
        private string $translatedDescription,
    ) {
    }

    public function translatedName(): string
    {
        return $this->translatedName;
    }

    public function translatedDescription(): string
    {
        return $this->translatedDescription;
    }
}
