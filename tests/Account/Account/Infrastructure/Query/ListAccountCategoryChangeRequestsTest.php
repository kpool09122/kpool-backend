<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests\ListAccountCategoryChangeRequestsInput;
use Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests\ListAccountCategoryChangeRequestsInterface;
use Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests\ListAccountCategoryChangeRequestsOutput;
use Source\Account\Account\Infrastructure\Query\ListAccountCategoryChangeRequests;
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

class ListAccountCategoryChangeRequestsTest extends TestCase
{
    public function test__construct(): void
    {
        $this->app->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));

        $this->assertInstanceOf(ListAccountCategoryChangeRequests::class, $this->app->make(ListAccountCategoryChangeRequestsInterface::class));
    }

    #[Group('useDb')]
    public function testProcessReturnsRequestsOrderedByRequestedAtAndId(): void
    {
        $operator = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $accountA = new AccountIdentifier(StrTestHelper::generateUuid());
        $accountB = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountA);
        CreateAccount::create((string) $accountB);

        $oldId = StrTestHelper::generateUuid();
        $newerLowId = '00000000-0000-0000-0000-000000000001';
        $newerHighId = 'ffffffff-ffff-ffff-ffff-ffffffffffff';
        $this->insertRequest($oldId, $accountA, 'pending', 'agency', '2026-08-10 10:00:00');
        $this->insertRequest($newerLowId, $accountA, 'approved', 'talent', '2026-08-11 10:00:00', (string) $operator->accountIdentifier());
        $this->insertRequest($newerHighId, $accountB, 'rejected', 'agency', '2026-08-11 10:00:00', (string) $operator->accountIdentifier(), ['code' => 'other', 'detail' => '書類不足']);

        $output = new ListAccountCategoryChangeRequestsOutput();
        (new ListAccountCategoryChangeRequests($this->allowingPolicyEvaluator($operator)))
            ->process(new ListAccountCategoryChangeRequestsInput($operator, perPage: 10), $output);

        $payload = $output->toArray();
        $this->assertSame([$newerHighId, $newerLowId, $oldId], array_column($payload['requests'], 'requestIdentifier'));
        $this->assertSame(['code' => 'other', 'detail' => '書類不足'], $payload['requests'][0]['rejectionReason']);
        $this->assertSame(1, $payload['current_page']);
        $this->assertSame(1, $payload['last_page']);
        $this->assertSame(3, $payload['total']);
        $this->assertSame(10, $payload['per_page']);
    }

    #[Group('useDb')]
    public function testProcessFiltersByStatusAndPagination(): void
    {
        $operator = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $anotherAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account);
        CreateAccount::create((string) $anotherAccount);
        $pending1 = StrTestHelper::generateUuid();
        $pending2 = StrTestHelper::generateUuid();
        $approved = StrTestHelper::generateUuid();
        $this->insertRequest($pending1, $account, 'pending', 'agency', '2026-08-10 10:00:00');
        $this->insertRequest($approved, $account, 'approved', 'talent', '2026-08-11 10:00:00', (string) $operator->accountIdentifier());
        $this->insertRequest($pending2, $anotherAccount, 'pending', 'talent', '2026-08-12 10:00:00');

        $output = new ListAccountCategoryChangeRequestsOutput();
        (new ListAccountCategoryChangeRequests($this->allowingPolicyEvaluator($operator)))
            ->process(new ListAccountCategoryChangeRequestsInput($operator, status: 'pending', perPage: 1, page: 2), $output);

        $payload = $output->toArray();
        $this->assertSame([$pending1], array_column($payload['requests'], 'requestIdentifier'));
        $this->assertSame(2, $payload['current_page']);
        $this->assertSame(2, $payload['last_page']);
        $this->assertSame(2, $payload['total']);
        $this->assertSame(1, $payload['per_page']);
    }

    #[Group('useDb')]
    public function testProcessFiltersByRequestedAccountCategory(): void
    {
        $operator = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $accountA = new AccountIdentifier(StrTestHelper::generateUuid());
        $accountB = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountA);
        CreateAccount::create((string) $accountB);
        $expected = StrTestHelper::generateUuid();
        $newerExpected = StrTestHelper::generateUuid();
        $this->insertRequest($expected, $accountA, 'pending', 'agency', '2026-08-10 10:00:00');
        $this->insertRequest($newerExpected, $accountB, 'pending', 'agency', '2026-08-11 10:00:00');
        $this->insertRequest(StrTestHelper::generateUuid(), $accountA, 'approved', 'talent', '2026-08-12 10:00:00', (string) $operator->accountIdentifier());

        $output = new ListAccountCategoryChangeRequestsOutput();
        (new ListAccountCategoryChangeRequests($this->allowingPolicyEvaluator($operator)))
            ->process(new ListAccountCategoryChangeRequestsInput($operator, requestedAccountCategory: 'agency'), $output);

        $this->assertSame([$newerExpected, $expected], array_column($output->toArray()['requests'], 'requestIdentifier'));
    }

    public function testProcessThrowsForbiddenWhenPolicyDoesNotAllow(): void
    {
        $operator = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnFalse();

        $this->expectException(AccountCategoryChangeRequestForbiddenException::class);

        (new ListAccountCategoryChangeRequests($policyEvaluator))->process(new ListAccountCategoryChangeRequestsInput($operator), new ListAccountCategoryChangeRequestsOutput());
    }

    private function allowingPolicyEvaluator(Principal $principal): PolicyEvaluatorInterface
    {
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->with($principal, Action::ACCOUNT_CATEGORY_CHANGE_REQUEST_MANAGE, Mockery::on(static fn (Resource $resource): bool => (string) $resource->accountIdentifier() === (string) $principal->accountIdentifier()))
            ->andReturnTrue();

        return $policyEvaluator;
    }

    /** @param array{code: string, detail: ?string}|null $rejectionReason */
    private function insertRequest(string $id, AccountIdentifier $accountIdentifier, string $status, string $requestedCategory, string $requestedAt, ?string $reviewedBy = null, ?array $rejectionReason = null): void
    {
        DB::table('account_category_change_requests')->insert([
            'id' => $id,
            'account_id' => (string) $accountIdentifier,
            'current_account_category' => 'general',
            'requested_account_category' => $requestedCategory,
            'status' => $status,
            'requested_at' => $requestedAt,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => $reviewedBy !== null ? '2026-08-12 12:00:00' : null,
            'rejection_reason' => $rejectionReason !== null ? json_encode($rejectionReason) : null,
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
