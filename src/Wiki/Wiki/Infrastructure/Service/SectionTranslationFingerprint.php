<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Service;

use Source\Wiki\Wiki\Domain\ValueObject\Section\Section;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentInterface;
use Source\Wiki\Wiki\Infrastructure\Repository\SectionContentMapper;

final class SectionTranslationFingerprint
{
    public static function of(SectionContentInterface $content): string
    {
        if ($content instanceof Section) {
            $content = new Section($content->title(), 0, new SectionContentCollection(array_values(array_filter(
                $content->contents()->all(),
                static fn (SectionContentInterface $child): bool => ! $child instanceof Section,
            ))));
        }

        return hash('sha256', serialize(self::textFields(SectionContentMapper::collectionToArray(new SectionContentCollection([$content])))));
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    private static function textFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && ! in_array($key, ['type', 'block_type', 'title', 'contents', 'content', 'source', 'caption', 'alt', 'items', 'header_cells', 'row_cells'], true)) {
                continue;
            }
            $fields[$key] = is_array($value) ? self::textFields($value) : $value;
        }

        return $fields;
    }
}
