<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Account\Affiliation\Application\UseCase\Query\AffiliationReadModel;

class AffiliationReadModelTest extends TestCase
{
    public function testToArrayReturnsAffiliationSummaryWithAccounts(): void
    {
        $readModel = new AffiliationReadModel(
            affiliationIdentifier: 'affiliation-id',
            agencyAccountIdentifier: 'agency-id',
            talentAccountIdentifier: 'talent-id',
            agencyAccount: [
                'accountIdentifier' => 'agency-id',
                'name' => 'Agency Account',
                'email' => 'agency@example.com',
            ],
            talentAccount: [
                'accountIdentifier' => 'talent-id',
                'name' => 'Talent Account',
                'email' => 'talent@example.com',
            ],
            requestedBy: 'agency-id',
            status: 'active',
            terms: ['revenueSharePercentage' => 30, 'contractNotes' => 'notes'],
            requestedAt: '2026-08-11T10:00:00+00:00',
            activatedAt: '2026-08-12T10:00:00+00:00',
            terminatedAt: null,
        );

        $this->assertSame([
            'affiliationIdentifier' => 'affiliation-id',
            'agencyAccountIdentifier' => 'agency-id',
            'talentAccountIdentifier' => 'talent-id',
            'agencyAccount' => [
                'accountIdentifier' => 'agency-id',
                'name' => 'Agency Account',
                'email' => 'agency@example.com',
            ],
            'talentAccount' => [
                'accountIdentifier' => 'talent-id',
                'name' => 'Talent Account',
                'email' => 'talent@example.com',
            ],
            'requestedBy' => 'agency-id',
            'status' => 'active',
            'terms' => ['revenueSharePercentage' => 30, 'contractNotes' => 'notes'],
            'requestedAt' => '2026-08-11T10:00:00+00:00',
            'activatedAt' => '2026-08-12T10:00:00+00:00',
            'terminatedAt' => null,
        ], $readModel->toArray());
    }

    public function testToArrayReturnsNullableFields(): void
    {
        $readModel = new AffiliationReadModel(
            affiliationIdentifier: 'affiliation-id',
            agencyAccountIdentifier: 'agency-id',
            talentAccountIdentifier: 'talent-id',
            agencyAccount: [
                'accountIdentifier' => 'agency-id',
                'name' => 'Agency Account',
                'email' => 'agency@example.com',
            ],
            talentAccount: [
                'accountIdentifier' => 'talent-id',
                'name' => 'Talent Account',
                'email' => 'talent@example.com',
            ],
            requestedBy: 'talent-id',
            status: 'pending',
            terms: null,
            requestedAt: '2026-08-11T10:00:00+00:00',
            activatedAt: null,
            terminatedAt: null,
        );

        $payload = $readModel->toArray();

        $this->assertNull($payload['terms']);
        $this->assertNull($payload['activatedAt']);
        $this->assertNull($payload['terminatedAt']);
    }
}
