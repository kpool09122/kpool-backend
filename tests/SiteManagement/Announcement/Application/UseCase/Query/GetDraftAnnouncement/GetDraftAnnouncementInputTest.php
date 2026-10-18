<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Announcement\Application\UseCase\Query\GetDraftAnnouncement;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Announcement\Application\UseCase\Query\GetDraftAnnouncement\GetDraftAnnouncementInput;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

class GetDraftAnnouncementInputTest extends TestCase
{
    public function testCarriesPrincipalAndAnnouncementIdentifiers(): void
    {
        $principal = new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000009');
        $announcement = new AnnouncementIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $input = new GetDraftAnnouncementInput($principal, $announcement);
        $this->assertSame($principal, $input->principalIdentifier());
        $this->assertSame($announcement, $input->announcementIdentifier());
    }
}
