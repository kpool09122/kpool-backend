<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient;

/** @phpstan-type GeneratedHistoryEntry array{year: int, title: string, detail: ?string} */
final class GeneratedHistoryEntries
{
    /** @return list<GeneratedHistoryEntry> */
    public static function fromValue(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $entries = [];
        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $year = $item['year'] ?? null;
            $title = $item['title'] ?? null;
            if (! is_int($year) || $year < 1000 || $year > 9999 || ! is_string($title) || trim($title) === '') {
                continue;
            }

            $detail = is_string($item['detail'] ?? null) ? trim($item['detail']) : '';
            $entries[] = ['year' => $year, 'title' => trim($title), 'detail' => $detail === '' ? null : $detail];
        }

        return $entries;
    }
}
