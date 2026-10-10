<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Principal\Application\UseCase\Command\GrantSiteManagementAdministrator\GrantSiteManagementAdministratorInput;

class GrantSiteManagementAdministratorInputTest extends TestCase
{
    public function testKeepsTheGivenEmailInstance(): void
    {
        $email = new Email('operator@example.com');

        $this->assertSame($email, (new GrantSiteManagementAdministratorInput($email))->email());
    }
}
