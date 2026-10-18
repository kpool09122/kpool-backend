<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query;

use DateTimeImmutable;

readonly class DraftAnnouncementReadModel
{
    /** @param array<string, array{title: string, content: string}> $contents */
    public function __construct(
        private string $announcementIdentifier,
        private string $sourceLanguage,
        private int $category,
        private array $contents,
        private DateTimeImmutable $publishedDate,
    ) {
    }

    /** @return array{announcementIdentifier: string, sourceLanguage: string, category: int, contents: array<string, array{title: string, content: string}>, publishedDate: string} */
    public function toArray(): array
    {
        return [
            'announcementIdentifier' => $this->announcementIdentifier,
            'sourceLanguage' => $this->sourceLanguage,
            'category' => $this->category,
            'contents' => $this->contents,
            'publishedDate' => $this->publishedDate->format('Y-m-d'),
        ];
    }
}
