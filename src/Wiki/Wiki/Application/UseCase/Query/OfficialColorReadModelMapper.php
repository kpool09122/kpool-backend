<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

final class OfficialColorReadModelMapper
{
    /**
     * @param mixed $officialColors
     * @return list<array{colorCode: string, label: string}>
     */
    public static function toApiArray(mixed $officialColors): array
    {
        if (! is_array($officialColors)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $color): array => self::colorToApiArray($color),
            $officialColors,
        ));
    }

    /**
     * @return array{colorCode: string, label: string}
     */
    private static function colorToApiArray(mixed $color): array
    {
        if (is_string($color)) {
            return [
                'colorCode' => $color,
                'label' => $color,
            ];
        }

        if (! is_array($color)) {
            return [
                'colorCode' => '',
                'label' => '',
            ];
        }

        $colorCode = $color['colorCode'] ?? $color['color_code'] ?? '';
        $label = $color['label'] ?? '';

        return [
            'colorCode' => is_string($colorCode) ? $colorCode : '',
            'label' => is_string($label) ? $label : '',
        ];
    }
}
