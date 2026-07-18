<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

final class OfficialColorReadModelMapper
{
    /**
     * @param list<array{color_code: string, label: string}> $officialColors
     * @return list<array{colorCode: string, label: string}>
     */
    public static function toArray(array $officialColors): array
    {
        return array_values(array_map(
            static fn (array $color): array => [
                'colorCode' => $color['color_code'],
                'label' => $color['label'],
            ],
            $officialColors,
        ));
    }
}
