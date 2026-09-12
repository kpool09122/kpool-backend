<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Delegation\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Delegation\Application\UseCase\Query\ListDelegations\ListDelegationsInput;
use Source\Account\Delegation\Application\UseCase\Query\ListDelegations\ListDelegationsInterface;
use Source\Account\Delegation\Application\UseCase\Query\ListDelegations\ListDelegationsOutput;
use Source\Account\Delegation\Infrastructure\Query\ListDelegations;
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

class ListDelegationsTest extends TestCase
{
    public function testConstruct(): void
    {
        $this->app->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));
        $this->assertInstanceOf(ListDelegations::class, $this->app->make(ListDelegationsInterface::class));
    }

    #[Group('useDb')]
    public function testReturnsOnlyRelatedDelegationsInStableOrderAndPaginates(): void
    {
        $operator = $this->principal();
        $related = new AccountIdentifier(StrTestHelper::generateUuid());
        $other1 = new AccountIdentifier(StrTestHelper::generateUuid());
        $other2 = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $operator->accountIdentifier(), ['category' => 'agency']);
        $old = '00000000-0000-0000-0000-000000000001';
        $sameTimeHigh = 'ffffffff-ffff-ffff-ffff-ffffffffffff';
        $sameTimeLow = '00000000-0000-0000-0000-000000000002';
        $this->insert($old, $operator->accountIdentifier(), $related, $operator->accountIdentifier(), 'pending', '2026-09-10 10:00:00');
        $this->insert($sameTimeLow, $related, $operator->accountIdentifier(), $related, 'approved', '2026-09-11 10:00:00', '2026-09-11 11:00:00');
        $this->insert($sameTimeHigh, $operator->accountIdentifier(), $related, $related, 'revoked', '2026-09-11 10:00:00', null, '2026-09-11 12:00:00');
        $this->insert(StrTestHelper::generateUuid(), $other1, $other2, $other1, 'pending', '2026-09-12 10:00:00');

        $output = new ListDelegationsOutput();
        (new ListDelegations($this->allowing($operator)))->process(new ListDelegationsInput($operator, perPage: 2), $output);
        $payload = $output->toArray();
        $this->assertSame([$sameTimeHigh, $sameTimeLow], array_column($payload['delegations'], 'delegationIdentifier'));
        $this->assertSame(3, $payload['total']);
        $this->assertSame(2, $payload['last_page']);
        $this->assertSame('2026-09-11T12:00:00+00:00', $payload['delegations'][0]['revokedAt']);
    }

    #[Group('useDb')]
    public function testFiltersPendingByRequesterAndApprover(): void
    {
        $operator = $this->principal();
        $counterpart = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $operator->accountIdentifier(), ['category' => 'agency']);
        $requested = StrTestHelper::generateUuid();
        $awaiting = StrTestHelper::generateUuid();
        $this->insert($requested, $operator->accountIdentifier(), $counterpart, $operator->accountIdentifier(), 'pending', '2026-09-10 10:00:00');
        $this->insert($awaiting, $counterpart, $operator->accountIdentifier(), $counterpart, 'pending', '2026-09-11 10:00:00');

        $requester = new ListDelegationsOutput();
        (new ListDelegations($this->allowing($operator)))->process(new ListDelegationsInput($operator, 'pending', 'requester'), $requester);
        $this->assertSame([$requested], array_column($requester->toArray()['delegations'], 'delegationIdentifier'));

        $approver = new ListDelegationsOutput();
        (new ListDelegations($this->allowing($operator)))->process(new ListDelegationsInput($operator, 'pending', 'approver'), $approver);
        $this->assertSame([$awaiting], array_column($approver->toArray()['delegations'], 'delegationIdentifier'));
    }

    #[Group('useDb')]
    public function testFiltersApprovedDelegations(): void
    {
        $operator = $this->principal();
        $counterpart = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $operator->accountIdentifier(), ['category' => 'agency']);
        $pending = StrTestHelper::generateUuid();
        $approved = StrTestHelper::generateUuid();
        $this->insert($pending, $operator->accountIdentifier(), $counterpart, $operator->accountIdentifier(), 'pending', '2026-09-10 10:00:00');
        $this->insert($approved, $operator->accountIdentifier(), $counterpart, $operator->accountIdentifier(), 'approved', '2026-09-11 10:00:00', '2026-09-11 11:00:00');
        $output = new ListDelegationsOutput();
        (new ListDelegations($this->allowing($operator)))->process(new ListDelegationsInput($operator, status: 'approved'), $output);
        $this->assertSame([$approved], array_column($output->toArray()['delegations'], 'delegationIdentifier'));
    }

    #[Group('useDb')]
    public function testThrowsForbiddenWhenPolicyDenies(): void
    {
        $operator = $this->principal();
        CreateAccount::create((string) $operator->accountIdentifier(), ['category' => 'agency']);
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policy */
        $policy = Mockery::mock(PolicyEvaluatorInterface::class);
        $policy->shouldReceive('evaluate')->times(4)->andReturnFalse();
        $this->expectException(DisallowedDelegationOperationException::class);
        (new ListDelegations($policy))->process(new ListDelegationsInput($operator), new ListDelegationsOutput());
    }

    private function allowing(Principal $principal): PolicyEvaluatorInterface
    {
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policy */
        $policy = Mockery::mock(PolicyEvaluatorInterface::class);
        $policy->shouldReceive('evaluate')->with($principal, Action::DELEGATION_APPROVE, Mockery::type(Resource::class))->andReturnTrue();

        return $policy;
    }

    private function insert(string $id, AccountIdentifier $delegate, AccountIdentifier $delegator, AccountIdentifier $requestedBy, string $status, string $requestedAt, ?string $approvedAt = null, ?string $revokedAt = null): void
    {
        DB::table('account_delegations')->insert([
            'id' => $id, 'affiliation_id' => StrTestHelper::generateUuid(),
            'delegate_account_id' => (string) $delegate, 'delegator_account_id' => (string) $delegator,
            'requested_by_account_id' => (string) $requestedBy, 'status' => $status,
            'direction' => 'from_agency', 'requested_at' => $requestedAt,
            'approved_at' => $approvedAt, 'revoked_at' => $revokedAt,
        ]);
    }

    private function principal(): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
    }
}
