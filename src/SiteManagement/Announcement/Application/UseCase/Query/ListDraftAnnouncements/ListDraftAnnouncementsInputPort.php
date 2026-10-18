<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements;

use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface ListDraftAnnouncementsInputPort
{
    public function perPage(): int;

    public function order(): string;

    public function sort(): string;

    public function searchWords(): string;

    public function principalIdentifier(): PrincipalIdentifier;
}
