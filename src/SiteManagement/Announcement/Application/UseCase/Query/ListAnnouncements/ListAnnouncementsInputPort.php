<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\ListAnnouncements;

use Source\Shared\Domain\ValueObject\Language;

interface ListAnnouncementsInputPort
{
    public function perPage(): int;

    public function order(): string;

    public function sort(): string;

    public function searchWords(): string;

    public function language(): Language;
}
