<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Service;

interface VideoTitleFilterServiceInterface
{
    /**
     * @param array<string, string> $titles Titles keyed by video ID.
     * @param array<string, list<string>> $namesByLanguage
     * @return array<string, string> Matching titles with their original keys.
     */
    public function filter(array $titles, array $namesByLanguage): array;
}
