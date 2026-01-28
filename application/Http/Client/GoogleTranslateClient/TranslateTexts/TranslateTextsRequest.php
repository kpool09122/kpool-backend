<?php

declare(strict_types=1);

namespace Application\Http\Client\GoogleTranslateClient\TranslateTexts;

final readonly class TranslateTextsRequest
{
    /**
     * @param string[] $texts
     * @param string $targetLanguage
     */
    public function __construct(
        private array $texts,
        private string $targetLanguage,
    ) {
    }

    /**
     * @return string[]
     */
    public function texts(): array
    {
        return $this->texts;
    }

    public function targetLanguage(): string
    {
        return $this->targetLanguage;
    }
}
