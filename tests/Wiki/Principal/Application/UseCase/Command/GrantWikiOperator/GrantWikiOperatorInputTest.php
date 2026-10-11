<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\GrantWikiOperator;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Email;
use Source\Wiki\Principal\Application\UseCase\Command\GrantWikiOperator\GrantWikiOperatorInput;

class GrantWikiOperatorInputTest extends TestCase
{
    public function testKeepsTheGivenEmailInstance(): void
    {
        $email = new Email('operator@example.com');

        $this->assertSame($email, (new GrantWikiOperatorInput($email))->email());
    }
}
