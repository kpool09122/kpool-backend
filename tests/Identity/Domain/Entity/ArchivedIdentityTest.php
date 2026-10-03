<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\Entity\ArchivedIdentity;
use Source\Identity\Domain\ValueObject\ArchivedIdentityIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;

class ArchivedIdentityTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $archivedIdentityIdentifier = new ArchivedIdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $language = Language::JAPANESE;
        $identityCreatedAt = new DateTimeImmutable('2026-10-03T01:02:03+00:00');
        $archivedAt = new DateTimeImmutable('2026-10-03T01:02:03+00:00');

        $subject = new ArchivedIdentity($archivedIdentityIdentifier, $identityIdentifier, $language, $identityCreatedAt, $archivedAt);

        $this->assertSame($archivedIdentityIdentifier, $subject->archivedIdentityIdentifier());
        $this->assertSame($identityIdentifier, $subject->identityIdentifier());
        $this->assertSame($language, $subject->language());
        $this->assertSame($identityCreatedAt, $subject->identityCreatedAt());
        $this->assertSame($archivedAt, $subject->archivedAt());
    }

    public function testAllowsAbsentOptionalValues(): void
    {
        $subject = new ArchivedIdentity(new ArchivedIdentityIdentifier('019c9b4c-0000-7000-8000-000000000001'), new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001'), Language::JAPANESE, null, new DateTimeImmutable('2026-10-03T01:02:03+00:00'));
        $this->assertNull($subject->identityCreatedAt());
    }
}
