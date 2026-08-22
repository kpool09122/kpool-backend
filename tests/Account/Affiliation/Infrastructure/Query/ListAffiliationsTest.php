<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations\ListAffiliationsInput;
use Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations\ListAffiliationsInterface;
use Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations\ListAffiliationsOutput;
use Source\Account\Affiliation\Infrastructure\Query\ListAffiliations;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListAffiliationsTest extends TestCase
{
    public function test__construct(): void
    {
        $this->app->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));

        $this->assertInstanceOf(ListAffiliations::class, $this->app->make(ListAffiliationsInterface::class));
    }

    #[Group('useDb')]
    public function testProcessReturnsRelatedAffiliationsOrderedByRequestedAtAndId(): void
    {
        $operator = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $agency = $operator->accountIdentifier();
        $talent = new AccountIdentifier(StrTestHelper::generateUuid());
        $other = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $agency, ['category' => 'agency', 'name' => 'Agency Alpha', 'email' => 'agency@example.com']);
        CreateAccount::create((string) $talent, ['category' => 'talent', 'name' => 'Talent Beta', 'email' => 'talent@example.com']);
        CreateAccount::create((string) $other, ['category' => 'talent', 'name' => 'Other Gamma', 'email' => 'other@example.com']);

        $oldId = StrTestHelper::generateUuid();
        $newerLowId = '00000000-0000-0000-0000-000000000001';
        $newerHighId = 'ffffffff-ffff-ffff-ffff-ffffffffffff';
        $unrelatedId = StrTestHelper::generateUuid();
        $this->insertAffiliation($oldId, $agency, $talent, $agency, 'pending', '2026-08-10 10:00:00');
        $this->insertAffiliation($newerLowId, $other, $agency, $other, 'active', '2026-08-11 10:00:00', 30, 'notes', '2026-08-12 10:00:00');
        $this->insertAffiliation($newerHighId, $agency, $other, $other, 'terminated', '2026-08-11 10:00:00', null, null, '2026-08-12 10:00:00', '2026-08-13 10:00:00');
        $this->insertAffiliation($unrelatedId, $talent, $other, $talent, 'pending', '2026-08-12 10:00:00');

        $output = new ListAffiliationsOutput();
        (new ListAffiliations($this->allowingPolicyEvaluator($operator)))
            ->process(new ListAffiliationsInput($operator, perPage: 10), $output);

        $payload = $output->toArray();
        $this->assertSame([$newerHighId, $newerLowId, $oldId], array_column($payload['affiliations'], 'affiliationIdentifier'));
        $this->assertSame([
            'accountIdentifier' => (string) $agency,
            'name' => 'Agency Alpha',
            'email' => 'agency@example.com',
        ], $payload['affiliations'][0]['agencyAccount']);
        $this->assertSame([
            'accountIdentifier' => (string) $other,
            'name' => 'Other Gamma',
            'email' => 'other@example.com',
        ], $payload['affiliations'][0]['talentAccount']);
        $this->assertSame([
            'accountIdentifier' => (string) $other,
            'name' => 'Other Gamma',
            'email' => 'other@example.com',
        ], $payload['affiliations'][1]['agencyAccount']);
        $this->assertSame([
            'accountIdentifier' => (string) $agency,
            'name' => 'Agency Alpha',
            'email' => 'agency@example.com',
        ], $payload['affiliations'][1]['talentAccount']);
        $this->assertSame(['revenueSharePercentage' => 30, 'contractNotes' => 'notes'], $payload['affiliations'][1]['terms']);
        $this->assertSame(1, $payload['current_page']);
        $this->assertSame(1, $payload['last_page']);
        $this->assertSame(3, $payload['total']);
        $this->assertSame(10, $payload['per_page']);
    }

    #[Group('useDb')]
    public function testProcessFiltersByStatusAndPagination(): void
    {
        $operator = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $agency = $operator->accountIdentifier();
        $pendingTalent1 = new AccountIdentifier(StrTestHelper::generateUuid());
        $pendingTalent2 = new AccountIdentifier(StrTestHelper::generateUuid());
        $activeTalent = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $agency, ['category' => 'agency']);
        CreateAccount::create((string) $pendingTalent1, ['category' => 'talent']);
        CreateAccount::create((string) $pendingTalent2, ['category' => 'talent']);
        CreateAccount::create((string) $activeTalent, ['category' => 'talent']);

        $pending1 = StrTestHelper::generateUuid();
        $pending2 = StrTestHelper::generateUuid();
        $active = StrTestHelper::generateUuid();
        $this->insertAffiliation($pending1, $agency, $pendingTalent1, $agency, 'pending', '2026-08-10 10:00:00');
        $this->insertAffiliation($active, $agency, $activeTalent, $activeTalent, 'active', '2026-08-11 10:00:00');
        $this->insertAffiliation($pending2, $agency, $pendingTalent2, $pendingTalent2, 'pending', '2026-08-12 10:00:00');

        $output = new ListAffiliationsOutput();
        (new ListAffiliations($this->allowingPolicyEvaluator($operator)))
            ->process(new ListAffiliationsInput($operator, status: 'pending', perPage: 1, page: 2), $output);

        $payload = $output->toArray();
        $this->assertSame([$pending1], array_column($payload['affiliations'], 'affiliationIdentifier'));
        $this->assertSame(2, $payload['current_page']);
        $this->assertSame(2, $payload['last_page']);
        $this->assertSame(2, $payload['total']);
        $this->assertSame(1, $payload['per_page']);
    }

    #[Group('useDb')]
    public function testProcessFiltersByRequesterAndApproverViewerRole(): void
    {
        $operator = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $agency = $operator->accountIdentifier();
        $requesterTalent = new AccountIdentifier(StrTestHelper::generateUuid());
        $approverTalent = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $agency, ['category' => 'agency']);
        CreateAccount::create((string) $requesterTalent, ['category' => 'talent']);
        CreateAccount::create((string) $approverTalent, ['category' => 'talent']);

        $requester = StrTestHelper::generateUuid();
        $approver = StrTestHelper::generateUuid();
        $this->insertAffiliation($requester, $agency, $requesterTalent, $agency, 'pending', '2026-08-10 10:00:00');
        $this->insertAffiliation($approver, $agency, $approverTalent, $approverTalent, 'pending', '2026-08-11 10:00:00');

        $requesterOutput = new ListAffiliationsOutput();
        (new ListAffiliations($this->allowingPolicyEvaluator($operator)))
            ->process(new ListAffiliationsInput($operator, viewerRole: 'requester'), $requesterOutput);
        $this->assertSame([$requester], array_column($requesterOutput->toArray()['affiliations'], 'affiliationIdentifier'));

        $approverOutput = new ListAffiliationsOutput();
        (new ListAffiliations($this->allowingPolicyEvaluator($operator)))
            ->process(new ListAffiliationsInput($operator, viewerRole: 'approver'), $approverOutput);
        $this->assertSame([$approver], array_column($approverOutput->toArray()['affiliations'], 'affiliationIdentifier'));
    }

    #[Group('useDb')]
    public function testProcessThrowsForbiddenWhenPolicyDoesNotAllow(): void
    {
        $operator = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        CreateAccount::create((string) $operator->accountIdentifier(), ['category' => 'agency']);
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->times(4)->andReturnFalse();

        $this->expectException(DisallowedAffiliationOperationException::class);

        (new ListAffiliations($policyEvaluator))->process(new ListAffiliationsInput($operator), new ListAffiliationsOutput());
    }

    private function allowingPolicyEvaluator(Principal $principal): PolicyEvaluatorInterface
    {
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->with($principal, Action::AFFILIATION_APPROVE, Mockery::on(static fn (Resource $resource): bool => (string) $resource->accountIdentifier() === (string) $principal->accountIdentifier()))
            ->andReturnTrue();

        return $policyEvaluator;
    }

    private function insertAffiliation(string $id, AccountIdentifier $agency, AccountIdentifier $talent, AccountIdentifier $requestedBy, string $status, string $requestedAt, ?int $revenueSharePercentage = null, ?string $contractNotes = null, ?string $activatedAt = null, ?string $terminatedAt = null): void
    {
        DB::table('account_affiliations')->insert([
            'id' => $id,
            'agency_account_id' => (string) $agency,
            'talent_account_id' => (string) $talent,
            'requested_by' => (string) $requestedBy,
            'status' => $status,
            'revenue_share_percentage' => $revenueSharePercentage,
            'contract_notes' => $contractNotes,
            'requested_at' => $requestedAt,
            'activated_at' => $activatedAt,
            'terminated_at' => $terminatedAt,
        ]);
    }

    private function principal(AccountIdentifier $accountIdentifier): Principal
    {
        return new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
        );
    }
}
