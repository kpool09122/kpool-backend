<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\Entity\ArchivedPrincipal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ArchivedPrincipalIdentifier;
use Source\Shared\Domain\ValueObject\ArchivedPrincipalType;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class ArchivedPrincipalTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $archivedPrincipalIdentifier = new ArchivedPrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $principalType = ArchivedPrincipalType::ACCOUNT;
        $principalId = 'principalId-value';
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $archivedAt = new DateTimeImmutable('2026-10-03T01:02:03+00:00');

        $subject = new ArchivedPrincipal($archivedPrincipalIdentifier, $identityIdentifier, $principalType, $principalId, $accountIdentifier, $archivedAt);

        $this->assertSame($archivedPrincipalIdentifier, $subject->archivedPrincipalIdentifier());
        $this->assertSame($identityIdentifier, $subject->identityIdentifier());
        $this->assertSame($principalType, $subject->principalType());
        $this->assertSame($principalId, $subject->principalId());
        $this->assertSame($accountIdentifier, $subject->accountIdentifier());
        $this->assertSame($archivedAt, $subject->archivedAt());
    }
}
