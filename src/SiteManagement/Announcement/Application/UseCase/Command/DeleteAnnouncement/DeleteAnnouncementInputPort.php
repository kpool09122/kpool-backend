<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;

interface DeleteAnnouncementInputPort
{
    public function userIdentifier(): UserIdentifier;

    public function translationSetIdentifier(): TranslationSetIdentifier;
}
