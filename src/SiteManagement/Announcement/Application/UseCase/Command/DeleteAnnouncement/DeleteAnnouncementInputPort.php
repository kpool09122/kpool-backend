<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;

interface DeleteAnnouncementInputPort
{
    public function translationSetIdentifier(): TranslationSetIdentifier;
}
