<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\GrantOperations;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\GrantOperations\GrantOperationsInput;
use Source\Shared\Domain\ValueObject\Email;

class GrantOperationsInputTest extends TestCase
{
    public function testEmailReturnsSameInstance(): void
    {
        $email = new Email('operator@example.com');

        self::assertSame($email, (new GrantOperationsInput($email))->email());
    }
}
