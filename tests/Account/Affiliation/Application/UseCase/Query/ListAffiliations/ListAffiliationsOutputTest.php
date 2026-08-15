<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Application\UseCase\Query\ListAffiliations;

use PHPUnit\Framework\TestCase;
use Source\Account\Affiliation\Application\UseCase\Query\AffiliationReadModel;
use Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations\ListAffiliationsOutput;

class ListAffiliationsOutputTest extends TestCase
{
    public function testToArrayReturnsAffiliations(): void
    {
        $output = new ListAffiliationsOutput();
        $output->output(
            [
                new AffiliationReadModel(
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
                ),
            ],
            2,
            5,
            41,
            10,
        );

        $this->assertSame([
            'affiliations' => [[
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
            ]],
            'current_page' => 2,
            'last_page' => 5,
            'total' => 41,
            'per_page' => 10,
        ], $output->toArray());
    }

    public function testToArrayReturnsEmptyAffiliationsByDefault(): void
    {
        $output = new ListAffiliationsOutput();

        $this->assertSame([
            'affiliations' => [],
            'current_page' => null,
            'last_page' => null,
            'total' => null,
            'per_page' => null,
        ], $output->toArray());
    }
}
