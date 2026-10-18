<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Infrastructure\Adapters\Repository;

use Application\Models\SiteManagement\Announcement as AnnouncementModel;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementContents;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\LocalizedAnnouncementContent;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;
use UnexpectedValueException;

class AnnouncementRepository implements AnnouncementRepositoryInterface
{
    public function findById(AnnouncementIdentifier $announcementIdentifier): ?Announcement
    {
        $model = AnnouncementModel::query()->find((string) $announcementIdentifier);
        if ($model === null) {
            return null;
        }
        $contents = [];
        foreach ($model->contents as $language => $content) {
            $contents[] = new LocalizedAnnouncementContent(Language::from($language), new Title($content['title']), new Content($content['content']));
        }

        return new Announcement(
            new AnnouncementIdentifier($model->id),
            Language::from($model->source_language),
            Category::from($model->category),
            new AnnouncementContents(...$contents),
            new PublishedDate(($model->published_date ?? throw new UnexpectedValueException('Publication date is missing.'))->toDateTimeImmutable()),
        );
    }

    public function save(Announcement $announcement): void
    {
        AnnouncementModel::query()->updateOrCreate(['id' => (string) $announcement->announcementIdentifier()], [
            'source_language' => $announcement->sourceLanguage()->value,
            'category' => $announcement->category()->value,
            'contents' => $announcement->contents()->toArray(),
            'published_date' => $announcement->publishedDate()->value(),
        ]);
    }
}
