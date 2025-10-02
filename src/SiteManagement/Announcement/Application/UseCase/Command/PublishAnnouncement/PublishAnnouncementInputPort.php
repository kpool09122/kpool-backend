<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;

interface PublishAnnouncementInputPort
{
    public function translationSetIdentifier(): TranslationSetIdentifier;
}
