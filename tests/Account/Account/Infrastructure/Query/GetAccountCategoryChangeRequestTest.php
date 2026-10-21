<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestNotFoundException;
use Source\Account\Account\Application\Service\AccountCategoryChangeRequestSnapshotServiceInterface;
use Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestInput;
use Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestOutput;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Infrastructure\Query\GetAccountCategoryChangeRequest;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateAccountCategoryChangeRequestAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetAccountCategoryChangeRequestTest extends TestCase
{
    public function test__construct(): void
    {
        $this->app()->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));

        $this->assertInstanceOf(GetAccountCategoryChangeRequest::class, $this->app()->make(GetAccountCategoryChangeRequestInterface::class));
    }

    #[Group('useDb')]
    public function testProcessReturnsImmutableAccountAndDocumentSnapshotWithCurrentReviewStatus(): void
    {
        $operator = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier, [
            'email' => 'account@example.com',
            'type' => 'corporation',
            'name' => 'Target Account',
            'status' => 'active',
            'category' => 'general',
        ]);
        DB::table('accounts')->where('id', (string) $accountIdentifier)->update([
            'phone' => '+81-90-1234-5678',
            'address_country_code' => 'JP',
            'address_administrative_area_code' => '13',
            'address_postal_code' => '100-0001',
            'address_locality' => '千代田区',
            'address_line1' => '千代田1-1',
            'address_line2' => '1F',
        ]);

        $requestId = StrTestHelper::generateUuid();
        $this->insertRequest($requestId, $accountIdentifier, 'pending', 'agency', '2026-08-11 10:00:00');
        $this->insertIdentityPrincipal((string) $accountIdentifier, 'Alice', 'alice@example.com');
        $this->insertIdentityPrincipal((string) $accountIdentifier, 'Bob', 'bob@example.com');
        $this->insertDocument((string) $accountIdentifier, 'business_registration', 'documents/business-license.png', '2026-08-11 11:00:00');
        $this->insertDocument((string) $accountIdentifier, 'representative_id', 'documents/identity.png', '2026-08-11 12:00:00');

        Storage::fake('verification-documents');
        $disk = Storage::disk('verification-documents');
        $disk->put('documents/business-license.png', 'license bytes');
        $disk->put('documents/identity.png', 'identity bytes');
        $account = $this->app()->make(AccountRepositoryInterface::class)->findById($accountIdentifier);
        $request = $this->app()->make(AccountCategoryChangeRequestRepositoryInterface::class)->findById(new AccountCategoryChangeRequestIdentifier($requestId));
        self::assertNotNull($account);
        self::assertNotNull($request);
        $this->app()->make(AccountCategoryChangeRequestSnapshotServiceInterface::class)->capture($request, $account);
        DB::table('accounts')->where('id', (string) $accountIdentifier)->update(['name' => 'Changed', 'email' => 'new@example.com', 'phone' => null, 'address_line1' => 'Changed', 'category' => 'agency']);
        DB::table('account_documents')->where('account_id', (string) $accountIdentifier)->delete();
        $disk->delete(['documents/business-license.png', 'documents/identity.png']);
        $request->approve($operator->accountIdentifier());
        $this->app()->make(AccountCategoryChangeRequestRepositoryInterface::class)->save($request);

        $output = new GetAccountCategoryChangeRequestOutput();
        (new GetAccountCategoryChangeRequest($this->allowingPolicyEvaluator($operator)))
            ->process(new GetAccountCategoryChangeRequestInput(new AccountCategoryChangeRequestIdentifier($requestId), $operator), $output);

        $payload = $output->toArray();
        self::assertTrue(array_key_exists('requestIdentifier', $payload['request']));
        $this->assertSame($requestId, $payload['request']['requestIdentifier']);
        $this->assertSame((string) $accountIdentifier, $payload['request']['accountIdentifier']);
        $this->assertSame('agency', $payload['request']['requestedAccountCategory']);
        $this->assertSame('account@example.com', $payload['account']['email']);
        $this->assertSame('Target Account', $payload['account']['name']);
        $this->assertSame('+81-90-1234-5678', $payload['account']['phone']);
        $this->assertSame([
            'countryCode' => 'JP',
            'administrativeAreaCode' => '13',
            'postalCode' => '100-0001',
            'locality' => '千代田区',
            'addressLine1' => '千代田1-1',
            'addressLine2' => '1F',
        ], $payload['account']['address']);
        $this->assertArrayNotHasKey('identities', $payload);
        $this->assertSame('general', $payload['account']['accountCategory']);
        $this->assertSame('approved', $payload['request']['status']);
        $this->assertNotNull($payload['request']['reviewedAt']);
        $this->assertSame((string) $operator->accountIdentifier(), $payload['request']['reviewedBy']);
        $this->assertSame(['license bytes', 'identity bytes'], array_map(static fn (array $document): ?string => $disk->get($document['documentPath']), $payload['documents']));
        $this->assertSame(['business_registration', 'representative_id'], array_column($payload['documents'], 'documentType'));
    }

    #[Group('useDb')]
    public function testProcessThrowsNotFoundWhenRequestDoesNotExist(): void
    {
        $operator = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));

        $this->expectException(AccountCategoryChangeRequestNotFoundException::class);

        (new GetAccountCategoryChangeRequest($this->allowingPolicyEvaluator($operator)))
            ->process(new GetAccountCategoryChangeRequestInput(new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid()), $operator), new GetAccountCategoryChangeRequestOutput());
    }

    public function testProcessThrowsForbiddenWhenPolicyDoesNotAllow(): void
    {
        $operator = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnFalse();

        $this->expectException(AccountCategoryChangeRequestForbiddenException::class);

        (new GetAccountCategoryChangeRequest($policyEvaluator))
            ->process(new GetAccountCategoryChangeRequestInput(new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid()), $operator), new GetAccountCategoryChangeRequestOutput());
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

    private function insertRequest(string $id, AccountIdentifier $accountIdentifier, string $status, string $requestedCategory, string $requestedAt): void
    {
        $account = DB::table('accounts')->where('id', (string) $accountIdentifier)->first();
        self::assertNotNull($account);
        DB::table('account_category_change_requests')->insert([
            ...CreateAccountCategoryChangeRequestAccount::columns(),
            'account_email' => $account->email,
            'account_name' => $account->name,
            'account_type' => $account->type,
            'account_status' => $account->status,
            'account_phone' => $account->phone ?? '+819012345678',
            'account_address_country_code' => $account->address_country_code ?? 'JP',
            'account_address_administrative_area_code' => $account->address_administrative_area_code,
            'account_address_postal_code' => $account->address_postal_code ?? '100-0001',
            'account_address_locality' => $account->address_locality ?? '千代田区',
            'account_address_line1' => $account->address_line1 ?? 'Address',
            'account_address_line2' => $account->address_line2,

            'id' => $id,
            'account_id' => (string) $accountIdentifier,
            'current_account_category' => 'general',
            'requested_account_category' => $requestedCategory,
            'status' => $status,
            'requested_at' => $requestedAt,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ]);
    }

    private function insertIdentityPrincipal(string $accountId, string $name, string $email): void
    {
        $identityId = StrTestHelper::generateUuid();
        CreateIdentity::create(new IdentityIdentifier($identityId), [
            'identity_name' => $name,
            'email' => $email,
        ]);
        DB::table('account_principals')->insert([
            'id' => StrTestHelper::generateUuid(),
            'identity_id' => $identityId,
            'account_id' => $accountId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertDocument(string $accountId, string $documentType, string $documentPath, string $uploadedAt): void
    {
        DB::table('account_documents')->insert([
            'account_id' => $accountId,
            'document_type' => $documentType,
            'document_path' => $documentPath,
            'uploaded_at' => $uploadedAt,
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
