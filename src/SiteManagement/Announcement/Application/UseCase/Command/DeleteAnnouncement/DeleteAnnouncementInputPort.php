<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface DeleteAnnouncementInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function translationSetIdentifier(): TranslationSetIdentifier;
}
