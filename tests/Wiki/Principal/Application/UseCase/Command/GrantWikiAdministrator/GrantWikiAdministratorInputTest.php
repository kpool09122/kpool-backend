<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\GrantWikiAdministrator;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Email;
use Source\Wiki\Principal\Application\UseCase\Command\GrantWikiAdministrator\GrantWikiAdministratorInput;

class GrantWikiAdministratorInputTest extends TestCase
{
    public function testKeepsTheGivenEmailInstance(): void
    {
        $email = new Email('operator@example.com');

        $this->assertSame($email, (new GrantWikiAdministratorInput($email))->email());
    }
}
