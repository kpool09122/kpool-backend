<?php

declare(strict_types=1);

namespace Application\Http\Client\GoogleTranslateClient\TranslateTexts;

final readonly class TranslateTextsParams
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        private array $params,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromArray(array $params): self
    {
        return new self($params);
    }

    /**
     * @return string[]
     */
    public function translatedTexts(): array
    {
        /** @var string[] $translatedTexts */
        $translatedTexts = $this->params['translated_texts'] ?? [];

        return $translatedTexts;
    }
}
