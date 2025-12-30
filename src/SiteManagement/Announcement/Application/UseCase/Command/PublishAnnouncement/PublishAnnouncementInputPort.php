<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;

interface PublishAnnouncementInputPort
{
    public function userIdentifier(): UserIdentifier;

    public function translationSetIdentifier(): TranslationSetIdentifier;
}
