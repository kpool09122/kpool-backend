<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Infrastructure\Adapters\Repository;

use Application\Models\SiteManagement\Announcement as AnnouncementModel;
use Application\Models\SiteManagement\DraftAnnouncement as DraftAnnouncementModel;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;

class AnnouncementRepository implements AnnouncementRepositoryInterface
{
    public function findById(AnnouncementIdentifier $announcementIdentifier): ?Announcement
    {
        $model = AnnouncementModel::query()
            ->where('id', (string)$announcementIdentifier)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toAnnouncementEntity($model);
    }

    public function findByTranslationSetIdentifier(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array {
        return AnnouncementModel::query()
            ->where('translation_set_identifier', (string)$translationSetIdentifier)
            ->get()
            ->map(fn (AnnouncementModel $model): Announcement => $this->toAnnouncementEntity($model))
            ->all();
    }

    public function findDraftById(AnnouncementIdentifier $announcementIdentifier): ?DraftAnnouncement
    {
        $model = DraftAnnouncementModel::query()
            ->where('id', (string)$announcementIdentifier)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDraftAnnouncementEntity($model);
    }

    public function findDraftsByTranslationSetIdentifier(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array {
        return DraftAnnouncementModel::query()
            ->where('translation_set_identifier', (string)$translationSetIdentifier)
            ->get()
            ->map(fn (DraftAnnouncementModel $model): DraftAnnouncement => $this->toDraftAnnouncementEntity($model))
            ->all();
    }

    public function save(Announcement $announcement): void
    {
        AnnouncementModel::query()->updateOrCreate(
            [
                'id' => (string)$announcement->announcementIdentifier(),
            ],
            [
                'translation_set_identifier' => (string)$announcement->translationSetIdentifier(),
                'language' => $announcement->language()->value,
                'category' => $announcement->category()->value,
                'title' => (string)$announcement->title(),
                'content' => (string)$announcement->content(),
                'published_date' => $announcement->publishedDate()->value(),
            ]
        );
    }

    public function saveDraft(DraftAnnouncement $draftAnnouncement): void
    {
        DraftAnnouncementModel::query()->updateOrCreate(
            [
                'id' => (string)$draftAnnouncement->announcementIdentifier(),
            ],
            [
                'translation_set_identifier' => (string)$draftAnnouncement->translationSetIdentifier(),
                'language' => $draftAnnouncement->translation()->value,
                'category' => $draftAnnouncement->category()->value,
                'title' => (string)$draftAnnouncement->title(),
                'content' => (string)$draftAnnouncement->content(),
                'published_date' => $draftAnnouncement->publishedDate()->value(),
            ]
        );
    }

    public function delete(Announcement $announcement): void
    {
        AnnouncementModel::query()
            ->where('id', (string)$announcement->announcementIdentifier())
            ->delete();
    }

    public function deleteDraft(DraftAnnouncement $draftAnnouncement): void
    {
        DraftAnnouncementModel::query()
            ->where('id', (string)$draftAnnouncement->announcementIdentifier())
            ->forceDelete();
    }

    private function toAnnouncementEntity(AnnouncementModel $model): Announcement
    {
        return new Announcement(
            new AnnouncementIdentifier($model->id),
            new TranslationSetIdentifier($model->translation_set_identifier),
            Language::from($model->language),
            Category::from((int)$model->category),
            new Title($model->title),
            new Content($model->content),
            new PublishedDate($model->published_date->toDateTimeImmutable()),
        );
    }

    private function toDraftAnnouncementEntity(DraftAnnouncementModel $model): DraftAnnouncement
    {
        return new DraftAnnouncement(
            new AnnouncementIdentifier($model->id),
            new TranslationSetIdentifier($model->translation_set_identifier),
            Language::from($model->language),
            Category::from((int)$model->category),
            new Title($model->title),
            new Content($model->content),
            new PublishedDate($model->published_date->toDateTimeImmutable()),
        );
    }
}
