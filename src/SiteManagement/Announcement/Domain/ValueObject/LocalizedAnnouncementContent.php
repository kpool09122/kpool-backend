<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Language;

readonly class LocalizedAnnouncementContent
{
    public function __construct(
        public Language $language,
        public Title $title,
        public Content $content,
    ) {
    }

    /** @return array{title: string, content: string} */
    public function toArray(): array
    {
        return ['title' => (string) $this->title, 'content' => (string) $this->content];
    }
}
