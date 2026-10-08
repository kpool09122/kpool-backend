<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface PublishAnnouncementInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function translationSetIdentifier(): TranslationSetIdentifier;
}
