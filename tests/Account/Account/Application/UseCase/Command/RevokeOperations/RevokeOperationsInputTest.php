<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\RevokeOperations;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\RevokeOperations\RevokeOperationsInput;
use Source\Shared\Domain\ValueObject\Email;

class RevokeOperationsInputTest extends TestCase
{
    public function testEmailReturnsSameInstance(): void
    {
        $email = new Email('operator@example.com');

        self::assertSame($email, (new RevokeOperationsInput($email))->email());
    }
}
