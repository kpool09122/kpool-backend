<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementOperator\RevokeSiteManagementOperatorInput;

class RevokeSiteManagementOperatorInputTest extends TestCase
{
    public function testKeepsTheGivenEmailInstance(): void
    {
        $email = new Email('operator@example.com');

        $this->assertSame($email, (new RevokeSiteManagementOperatorInput($email))->email());
    }
}
