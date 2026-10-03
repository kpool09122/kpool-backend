<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\AccountDocumentFileType;

class AccountDocumentFileTypeTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'PDF' => 'application/pdf',
            'JPEG' => 'image/jpeg',
            'PNG' => 'image/png',
            'WEBP' => 'image/webp',
            'HEIC' => 'image/heic',
            'HEIF' => 'image/heif',
        ], array_column(AccountDocumentFileType::cases(), 'value', 'name'));
    }

    public function testMimeTypesAndExtensions(): void
    {
        $this->assertSame('pdf', AccountDocumentFileType::PDF->extension());
        $this->assertSame(AccountDocumentFileType::PDF, AccountDocumentFileType::tryFromMimeType(AccountDocumentFileType::PDF->value));
        $this->assertSame('jpg', AccountDocumentFileType::JPEG->extension());
        $this->assertSame(AccountDocumentFileType::JPEG, AccountDocumentFileType::tryFromMimeType(AccountDocumentFileType::JPEG->value));
        $this->assertSame('png', AccountDocumentFileType::PNG->extension());
        $this->assertSame(AccountDocumentFileType::PNG, AccountDocumentFileType::tryFromMimeType(AccountDocumentFileType::PNG->value));
        $this->assertSame('webp', AccountDocumentFileType::WEBP->extension());
        $this->assertSame(AccountDocumentFileType::WEBP, AccountDocumentFileType::tryFromMimeType(AccountDocumentFileType::WEBP->value));
        $this->assertSame('heic', AccountDocumentFileType::HEIC->extension());
        $this->assertSame(AccountDocumentFileType::HEIC, AccountDocumentFileType::tryFromMimeType(AccountDocumentFileType::HEIC->value));
        $this->assertSame('heif', AccountDocumentFileType::HEIF->extension());
        $this->assertSame(AccountDocumentFileType::HEIF, AccountDocumentFileType::tryFromMimeType(AccountDocumentFileType::HEIF->value));
        $this->assertNull(AccountDocumentFileType::tryFromMimeType('text/plain'));
    }
}
