<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountCategoryChangeRequest\GetLatestAccountCategoryChangeRequestInput;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountCategoryChangeRequest\GetLatestAccountCategoryChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountCategoryChangeRequest\GetLatestAccountCategoryChangeRequestOutput;
use Source\Account\Account\Infrastructure\Query\GetLatestAccountCategoryChangeRequest;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateAccountCategoryChangeRequestAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetLatestAccountCategoryChangeRequestTest extends TestCase
{
    public function testBinding(): void
    {
        $this->assertInstanceOf(GetLatestAccountCategoryChangeRequest::class, $this->app()->make(GetLatestAccountCategoryChangeRequestInterface::class));
    }

    public function testAnotherAccountIsForbidden(): void
    {
        $this->expectException(AccountCategoryChangeRequestForbiddenException::class);
        (new GetLatestAccountCategoryChangeRequest())->process($this->input(new AccountIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid())), new GetLatestAccountCategoryChangeRequestOutput());
    }

    #[Group('useDb')]
    public function testNoRequestsAndOnlyWithdrawnReturnNull(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountId);
        $query = new GetLatestAccountCategoryChangeRequest();
        $output = new GetLatestAccountCategoryChangeRequestOutput();
        $query->process($this->input($accountId, $accountId), $output);
        $this->assertSame(['request' => null], $output->toArray());
        $this->insertRequest(StrTestHelper::generateUuid(), $accountId, 'withdrawn', '2026-08-12 10:00:00');
        $output = new GetLatestAccountCategoryChangeRequestOutput();
        $query->process($this->input($accountId, $accountId), $output);
        $this->assertSame(['request' => null], $output->toArray());
    }

    /** @return array<string, array{string}> */
    public static function visibleStatuses(): array
    {
        return ['pending' => ['pending'], 'approved' => ['approved'], 'rejected' => ['rejected']];
    }

    #[Group('useDb')]
    #[DataProvider('visibleStatuses')]
    public function testLatestDateWinsRegardlessOfStatusAndExcludesWithdrawnAndOtherAccounts(string $status): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $otherId = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountId);
        CreateAccount::create((string) $otherId);
        $this->insertRequest(StrTestHelper::generateUuid(), $accountId, $status === 'pending' ? 'approved' : 'pending', '2026-08-10 10:00:00');
        $latestId = StrTestHelper::generateUuid();
        $this->insertRequest($latestId, $accountId, $status, '2026-08-11 10:00:00');
        $this->insertRequest(StrTestHelper::generateUuid(), $accountId, 'withdrawn', '2026-08-12 10:00:00');
        $this->insertRequest(StrTestHelper::generateUuid(), $otherId, 'approved', '2026-08-13 10:00:00');
        $output = new GetLatestAccountCategoryChangeRequestOutput();
        (new GetLatestAccountCategoryChangeRequest())->process($this->input($accountId, $accountId), $output);
        $payload = $output->toArray()['request'];
        $this->assertNotNull($payload);
        $this->assertSame([
            'requestIdentifier' => $latestId,
            'accountIdentifier' => (string) $accountId,
            'currentAccountCategory' => 'general',
            'requestedAccountCategory' => 'agency',
            'status' => $status,
            'requestedAt' => '2026-08-11T10:00:00+00:00',
            'reviewedBy' => null,
            'reviewedAt' => null,
            'rejectionReason' => null,
        ], $payload);
    }

    #[Group('useDb')]
    public function testSameDateUsesDescendingIdentifierAndReturnsReviewFields(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountId);
        $low = '00000000-0000-4000-8000-000000000001';
        $high = '00000000-0000-4000-8000-000000000002';
        $this->insertRequest($low, $accountId, 'approved', '2026-08-11 10:00:00');
        $this->insertRequest($high, $accountId, 'rejected', '2026-08-11 10:00:00');
        DB::table('account_category_change_requests')->where('id', $high)->update(['reviewed_by' => (string) $accountId, 'reviewed_at' => '2026-08-12 10:00:00', 'rejection_reason' => json_encode(['code' => 'other', 'detail' => 'reason'])]);
        $output = new GetLatestAccountCategoryChangeRequestOutput();
        (new GetLatestAccountCategoryChangeRequest())->process($this->input($accountId, $accountId), $output);
        $payload = $output->toArray()['request'];
        $this->assertNotNull($payload);
        $this->assertSame($high, $payload['requestIdentifier']);
        $this->assertSame((string) $accountId, $payload['reviewedBy']);
        $this->assertSame('2026-08-12T10:00:00+00:00', $payload['reviewedAt']);
        $this->assertSame(['code' => 'other', 'detail' => 'reason'], $payload['rejectionReason']);
    }

    private function input(AccountIdentifier $accountId, AccountIdentifier $principalAccountId): GetLatestAccountCategoryChangeRequestInput
    {
        return new GetLatestAccountCategoryChangeRequestInput($accountId, new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $principalAccountId));
    }

    private function insertRequest(string $id, AccountIdentifier $accountId, string $status, string $requestedAt): void
    {
        DB::table('account_category_change_requests')->insert([
            ...CreateAccountCategoryChangeRequestAccount::columns(),'id' => $id, 'account_id' => (string) $accountId, 'current_account_category' => 'general', 'requested_account_category' => 'agency', 'status' => $status, 'requested_at' => $requestedAt]);
    }
}
