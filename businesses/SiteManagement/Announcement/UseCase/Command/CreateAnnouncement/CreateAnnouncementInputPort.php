<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Announcement\UseCase\Command\CreateAnnouncement;

use Businesses\Shared\ValueObject\Translation;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Category;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Content;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Title;

interface CreateAnnouncementInputPort
{
    public function translation(): Translation;

    public function category(): Category;

    public function title(): Title;

    public function content(): Content;

    public function publishedDate(): PublishedDate;
}
