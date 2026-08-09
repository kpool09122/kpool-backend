<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Domain\ValueObject\AccountDocumentFileType;
use Source\Account\Account\Infrastructure\Service\AccountDocumentFileTypeDetector;
use Tests\TestCase;

class AccountDocumentFileTypeDetectorTest extends TestCase
{
    #[DataProvider('allowedFileProvider')]
    public function testDetectsAllowedFileType(string $contents, AccountDocumentFileType $expected): void
    {
        $this->assertSame($expected, (new AccountDocumentFileTypeDetector())->detect($contents));
    }

    public function testRejectsUnsupportedFileType(): void
    {
        $this->expectException(InvalidDocumentsForVerificationException::class);

        (new AccountDocumentFileTypeDetector())->detect('plain text');
    }

    /**
     * @return array<string, array{string, AccountDocumentFileType}>
     */
    public static function allowedFileProvider(): array
    {
        return [
            'pdf' => ["%PDF-1.4\n1 0 obj\n<<>>\nendobj\n", AccountDocumentFileType::PDF],
            'jpeg' => ["\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00\x48\x00\x48\x00\x00\xFF\xD9", AccountDocumentFileType::JPEG],
            'png' => ["\x89PNG\r\n\x1A\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x02\x00\x00\x00\x90wS\xDE", AccountDocumentFileType::PNG],
            'webp' => ["RIFF\x1A\x00\x00\x00WEBPVP8 \x0E\x00\x00\x00\x10\x00\x00\x9D\x01\x2A\x01\x00\x01\x00", AccountDocumentFileType::WEBP],
            'heic' => ["\x00\x00\x00\x18ftypheic\x00\x00\x00\x00heicmif1", AccountDocumentFileType::HEIC],
            'heif' => ["\x00\x00\x00\x18ftypmif1\x00\x00\x00\x00mif1heic", AccountDocumentFileType::HEIF],
        ];
    }
}
