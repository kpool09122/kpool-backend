<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject;

enum WikiFontStyle: string
{
    case JA_POP = 'ja_pop';
    case JA_GOTHIC = 'ja_gothic';
    case JA_MINCHO = 'ja_mincho';
    case JA_ARTISTIC = 'ja_artistic';
    case JA_HANDWRITTEN = 'ja_handwritten';
    case KO_ROUNDED = 'ko_rounded';
    case KO_GOTHIC = 'ko_gothic';
    case KO_MYUNGJO = 'ko_myungjo';
    case KO_MODERN = 'ko_modern';
    case KO_HANDWRITTEN = 'ko_handwritten';
    case EN_SANS = 'en_sans';
    case EN_SERIF = 'en_serif';
    case EN_DISPLAY = 'en_display';
    case EN_MODERN = 'en_modern';
    case EN_HANDWRITTEN = 'en_handwritten';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<array{id: string, language: string, category: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $fontStyle): array => $fontStyle->option(),
            self::cases(),
        );
    }

    /**
     * @return array{id: string, language: string, category: string, label: string}
     */
    public function option(): array
    {
        return match ($this) {
            self::JA_POP => $this->toOption('ja', 'pop', 'ポップ'),
            self::JA_GOTHIC => $this->toOption('ja', 'gothic', 'ゴシック'),
            self::JA_MINCHO => $this->toOption('ja', 'mincho', '明朝'),
            self::JA_ARTISTIC => $this->toOption('ja', 'artistic', 'アーティスティック'),
            self::JA_HANDWRITTEN => $this->toOption('ja', 'handwritten', '手書き'),
            self::KO_ROUNDED => $this->toOption('ko', 'rounded', '라운드'),
            self::KO_GOTHIC => $this->toOption('ko', 'gothic', '고딕'),
            self::KO_MYUNGJO => $this->toOption('ko', 'myungjo', '명조'),
            self::KO_MODERN => $this->toOption('ko', 'modern', '모던'),
            self::KO_HANDWRITTEN => $this->toOption('ko', 'handwritten', '손글씨'),
            self::EN_SANS => $this->toOption('en', 'sans', 'Sans'),
            self::EN_SERIF => $this->toOption('en', 'serif', 'Serif'),
            self::EN_DISPLAY => $this->toOption('en', 'display', 'Display'),
            self::EN_MODERN => $this->toOption('en', 'modern', 'Modern'),
            self::EN_HANDWRITTEN => $this->toOption('en', 'handwritten', 'Handwritten'),
        };
    }

    /**
     * @return array{id: string, language: string, category: string, label: string}
     */
    private function toOption(string $language, string $category, string $label): array
    {
        return [
            'id' => $this->value,
            'language' => $language,
            'category' => $category,
            'label' => $label,
        ];
    }
}
