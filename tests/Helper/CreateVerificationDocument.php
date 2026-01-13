<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;

class CreateVerificationDocument
{
    /**
     * @param array{
     *     document_type?: string,
     *     document_path?: string,
     *     original_file_name?: string,
     *     file_size_bytes?: int,
     *     uploaded_at?: DateTimeImmutable,
     * } $overrides
     */
    public static function create(
        DocumentIdentifier $documentIdentifier,
        VerificationIdentifier $verificationIdentifier,
        array $overrides = [],
    ): void {
        DB::table('verification_documents')->insert([
            'id' => (string) $documentIdentifier,
            'verification_id' => (string) $verificationIdentifier,
            'document_type' => $overrides['document_type'] ?? 'passport',
            'document_path' => $overrides['document_path'] ?? '/verification/documents/test.jpg',
            'original_file_name' => $overrides['original_file_name'] ?? 'test.jpg',
            'file_size_bytes' => $overrides['file_size_bytes'] ?? 1024,
            'uploaded_at' => $overrides['uploaded_at'] ?? now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
