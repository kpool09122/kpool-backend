<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\DeleteAccountData;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\UseCase\Command\DeleteAccountData\DeleteAccountDataInput;

class DeleteAccountDataInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');

        $subject = new DeleteAccountDataInput($accountIdentifier);

        $this->assertSame($accountIdentifier, $subject->accountIdentifier());
    }
}
