<?php

declare(strict_types=1);

namespace Application\Http\Client\GoogleTranslateClient\TranslateTexts;

final readonly class TranslateTextsResponse
{
    /**
     * @param string[] $translatedTexts
     */
    public function __construct(
        private array $translatedTexts,
    ) {
    }

    public function params(): TranslateTextsParams
    {
        return TranslateTextsParams::fromArray(['translated_texts' => $this->translatedTexts]);
    }

    /**
     * @return string[]
     */
    public function translatedTexts(): array
    {
        return $this->translatedTexts;
    }
}
