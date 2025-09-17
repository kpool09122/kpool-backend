<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Announcement\UseCase\Query;

use DateTimeImmutable;

readonly class AnnouncementReadModel
{
    /**
     * @param string $announcementId
     * @param string $categoryName
     * @param string $title
     * @param string $content
     * @param DateTimeImmutable $publishedDate
     */
    public function __construct(
        private string $announcementId,
        private string $categoryName,
        private string $title,
        private string $content,
        private DateTimeImmutable $publishedDate,
    ) {
    }

    public function announcementId(): string
    {
        return $this->announcementId;
    }

    public function categoryName(): string
    {
        return $this->categoryName;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function publishedDate(): DateTimeImmutable
    {
        return $this->publishedDate;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'announcement_id' => $this->announcementId,
            'category_name' => $this->categoryName,
            'title' => $this->title,
            'content' => $this->content,
            'published_date' => $this->publishedDate->format('Y-m-d'),
        ];
    }
}
