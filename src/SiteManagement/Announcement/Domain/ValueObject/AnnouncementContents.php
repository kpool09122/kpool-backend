<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;

readonly class AnnouncementContents
{
    /** @var array<string, LocalizedAnnouncementContent> */
    private array $contents;

    public function __construct(LocalizedAnnouncementContent ...$contents)
    {
        $indexed = [];
        foreach ($contents as $content) {
            if (isset($indexed[$content->language->value])) {
                throw new InvalidArgumentException('Duplicate announcement language.');
            }
            $indexed[$content->language->value] = $content;
        }
        $this->contents = $indexed;
    }

    public function get(Language $language): ?LocalizedAnnouncementContent
    {
        return $this->contents[$language->value] ?? null;
    }

    public function with(LocalizedAnnouncementContent $content): self
    {
        $contents = $this->contents;
        $contents[$content->language->value] = $content;

        return new self(...array_values($contents));
    }

    /** @return array<string, array{title: string, content: string}> */
    public function toArray(): array
    {
        return array_map(static fn (LocalizedAnnouncementContent $content): array => $content->toArray(), $this->contents);
    }
}
