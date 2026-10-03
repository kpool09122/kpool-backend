<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\Entity\ArchivedAccount;
use Source\Account\Account\Domain\ValueObject\ArchivedAccountIdentifier;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class ArchivedAccountTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $archivedAccountIdentifier = new ArchivedAccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $accountCategory = AccountCategory::AGENCY;
        $accountType = AccountType::CORPORATION;
        $archivedAt = new DateTimeImmutable('2026-10-03T01:02:03+00:00');

        $subject = new ArchivedAccount($archivedAccountIdentifier, $accountIdentifier, $accountCategory, $accountType, $archivedAt);

        $this->assertSame($archivedAccountIdentifier, $subject->archivedAccountIdentifier());
        $this->assertSame($accountIdentifier, $subject->accountIdentifier());
        $this->assertSame($accountCategory, $subject->accountCategory());
        $this->assertSame($accountType, $subject->accountType());
        $this->assertSame($archivedAt, $subject->archivedAt());
    }
}
