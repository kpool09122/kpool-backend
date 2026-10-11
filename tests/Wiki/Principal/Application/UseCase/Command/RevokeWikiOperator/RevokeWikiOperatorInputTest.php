<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Email;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator\RevokeWikiOperatorInput;

class RevokeWikiOperatorInputTest extends TestCase
{
    public function testKeepsTheGivenEmailInstance(): void
    {
        $email = new Email('operator@example.com');

        $this->assertSame($email, (new RevokeWikiOperatorInput($email))->email());
    }
}
