<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Infrastructure\Adapters\Repository;

use Application\Models\SiteManagement\DraftAnnouncement as DraftAnnouncementModel;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\Repository\DraftAnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementContents;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\LocalizedAnnouncementContent;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;

class DraftAnnouncementRepository implements DraftAnnouncementRepositoryInterface
{
    public function findById(AnnouncementIdentifier $announcementIdentifier): ?DraftAnnouncement
    {
        $model = DraftAnnouncementModel::query()->find((string) $announcementIdentifier);
        if ($model === null) {
            return null;
        }
        $contents = [];
        foreach ($model->contents as $language => $content) {
            $contents[] = new LocalizedAnnouncementContent(Language::from($language), new Title($content['title']), new Content($content['content']));
        }

        return new DraftAnnouncement(
            new AnnouncementIdentifier($model->id),
            Language::from($model->source_language),
            Category::from($model->category),
            new AnnouncementContents(...$contents),
        );
    }

    public function save(DraftAnnouncement $draftAnnouncement): void
    {
        DraftAnnouncementModel::query()->updateOrCreate(['id' => (string) $draftAnnouncement->announcementIdentifier()], [
            'source_language' => $draftAnnouncement->sourceLanguage()->value,
            'category' => $draftAnnouncement->category()->value,
            'contents' => $draftAnnouncement->contents()->toArray(),
        ]);
    }

    public function delete(DraftAnnouncement $draftAnnouncement): void
    {
        DraftAnnouncementModel::query()->where('id', (string) $draftAnnouncement->announcementIdentifier())->delete();
    }
}
