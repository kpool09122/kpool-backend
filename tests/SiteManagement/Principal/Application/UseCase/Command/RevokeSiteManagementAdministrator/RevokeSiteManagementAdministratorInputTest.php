<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator\RevokeSiteManagementAdministratorInput;

class RevokeSiteManagementAdministratorInputTest extends TestCase
{
    public function testKeepsTheGivenEmailInstance(): void
    {
        $email = new Email('operator@example.com');

        $this->assertSame($email, (new RevokeSiteManagementAdministratorInput($email))->email());
    }
}
