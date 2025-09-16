<?php

namespace Businesses\SiteManagement\Announcement\UseCase\Query\GetAnnouncements;

interface GetAnnouncementsInputPort
{
    public function limit(): int;

    public function order(): string;

    public function sort(): string;

    public function searchWords(): string;
}
