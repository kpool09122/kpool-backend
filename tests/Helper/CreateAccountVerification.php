<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class CreateAccountVerification
{
    /**
     * @param array{
     *     verification_type?: string,
     *     status?: string,
     *     applicant_info?: array<string, mixed>,
     *     requested_at?: DateTimeImmutable,
     *     reviewed_by?: string|null,
     *     reviewed_at?: DateTimeImmutable|null,
     *     rejection_reason?: array<string, mixed>|null,
     *     created_at?: DateTimeImmutable,
     * } $overrides
     */
    public static function create(
        VerificationIdentifier $verificationIdentifier,
        AccountIdentifier $accountIdentifier,
        array $overrides = [],
    ): void {
        DB::table('account_verifications')->insert([
            'id' => (string) $verificationIdentifier,
            'account_id' => (string) $accountIdentifier,
            'verification_type' => $overrides['verification_type'] ?? 'talent',
            'status' => $overrides['status'] ?? 'pending',
            'applicant_info' => json_encode($overrides['applicant_info'] ?? [
                'full_name' => 'Test User',
                'company_name' => null,
                'representative_name' => null,
            ]),
            'requested_at' => $overrides['requested_at'] ?? now(),
            'reviewed_by' => $overrides['reviewed_by'] ?? null,
            'reviewed_at' => $overrides['reviewed_at'] ?? null,
            'rejection_reason' => isset($overrides['rejection_reason'])
                ? json_encode($overrides['rejection_reason'])
                : null,
            'created_at' => $overrides['created_at'] ?? now(),
            'updated_at' => $overrides['created_at'] ?? now(),
        ]);
    }
}
