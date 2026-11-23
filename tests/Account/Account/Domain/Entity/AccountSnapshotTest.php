<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\Entity\AccountSnapshot;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotDocument;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccountSnapshotProfile;
use Tests\Helper\StrTestHelper;

class AccountSnapshotTest extends TestCase
{
    public function testMatchesProfileAndDocumentContentsRegardlessOfDocumentOrder(): void
    {
        self::assertTrue($this->snapshot()->hasSameContents(CreateAccountSnapshotProfile::create(), [
            'selfie' => hash('sha256', 'selfie'),
            'passport' => hash('sha256', 'passport'),
        ]));
    }

    public function testDetectsProfileChangesAndDocumentReplacementAdditionOrRemoval(): void
    {
        $snapshot = $this->snapshot();
        $hashes = ['passport' => hash('sha256', 'passport'), 'selfie' => hash('sha256', 'selfie')];

        self::assertFalse($snapshot->hasSameContents(CreateAccountSnapshotProfile::create(locality: '新宿区'), $hashes));
        self::assertFalse($snapshot->hasSameContents(CreateAccountSnapshotProfile::create(), array_replace($hashes, ['passport' => hash('sha256', 'replacement')])));
        self::assertFalse($snapshot->hasSameContents(CreateAccountSnapshotProfile::create(), $hashes + ['driver_license' => hash('sha256', 'license')]));
        self::assertFalse($snapshot->hasSameContents(CreateAccountSnapshotProfile::create(), ['passport' => $hashes['passport']]));
    }

    public function testComputesNextVersionWithoutChangingSavedVersion(): void
    {
        $snapshot = $this->snapshot(version: 5);

        self::assertSame(6, $snapshot->nextVersion());
        self::assertSame(5, $snapshot->version());
    }

    public function testRejectsZeroVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->snapshot(version: 0);
    }

    private function snapshot(int $version = 1): AccountSnapshot
    {
        $savedAt = new DateTimeImmutable('2026-01-01');

        return new AccountSnapshot(
            new AccountSnapshotIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            $version,
            CreateAccountSnapshotProfile::create(),
            [
                new AccountSnapshotDocument(DocumentType::PASSPORT, new DocumentPath('saved/passport.pdf'), $savedAt, hash('sha256', 'passport')),
                new AccountSnapshotDocument(DocumentType::SELFIE, new DocumentPath('saved/selfie.pdf'), $savedAt, hash('sha256', 'selfie')),
            ],
            $savedAt,
        );
    }
}
