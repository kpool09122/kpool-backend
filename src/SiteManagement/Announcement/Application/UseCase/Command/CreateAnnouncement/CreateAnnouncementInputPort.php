<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;

interface CreateAnnouncementInputPort
{
    public function translationSetIdentifier(): ?TranslationSetIdentifier;

    public function translation(): Translation;

    public function category(): Category;

    public function title(): Title;

    public function content(): Content;

    public function publishedDate(): PublishedDate;
}
