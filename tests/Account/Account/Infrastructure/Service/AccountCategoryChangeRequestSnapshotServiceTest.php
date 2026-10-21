<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Service;

use Application\Models\Account\AccountCategoryChangeRequest as AccountCategoryChangeRequestModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Application\Service\AccountCategoryChangeRequestSnapshotServiceInterface;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Account\Account\Domain\Factory\AccountCategoryChangeRequestFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestAccount;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class AccountCategoryChangeRequestSnapshotServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('verification-documents');
    }

    public function testCopiesAllAccountFieldsToFixedColumnsAndDocumentsToChildRows(): void
    {
        [$request, $account] = $this->createRequest();
        $this->service()->capture($request, $account);
        $snapshot = AccountCategoryChangeRequestModel::query()->with('documents')->findOrFail((string) $request->requestIdentifier());
        $this->assertSame((string) $account->accountIdentifier(), $snapshot->account_id);
        $this->assertSame((string) $account->email(), $snapshot->account_email);
        $this->assertSame((string) $account->name(), $snapshot->account_name);
        $this->assertSame($account->type()?->value, $snapshot->account_type);
        $this->assertSame($account->status()->value, $snapshot->account_status);
        $this->assertSame($account->accountCategory()->value, $snapshot->current_account_category);
        $this->assertSame((string) $account->phone(), $snapshot->account_phone);
        $address = $account->address()?->toArray();
        $this->assertSame($address['countryCode'] ?? null, $snapshot->account_address_country_code);
        $this->assertSame($address['administrativeAreaCode'] ?? null, $snapshot->account_address_administrative_area_code);
        $this->assertSame($address['postalCode'] ?? null, $snapshot->account_address_postal_code);
        $this->assertSame($address['locality'] ?? null, $snapshot->account_address_locality);
        $this->assertSame($address['addressLine1'] ?? null, $snapshot->account_address_line1);
        $this->assertSame($address['addressLine2'] ?? null, $snapshot->account_address_line2);
        $disk = Storage::disk('verification-documents');
        $disk->delete('original.pdf');
        $this->assertCount(1, $snapshot->documents);
        $this->assertSame('passport', $snapshot->documents->firstOrFail()->document_type);
        $this->assertSame('2026-10-21 00:00:00', $snapshot->documents->firstOrFail()->uploaded_at->format('Y-m-d H:i:s'));
        $this->assertStringStartsWith('accounts/' . $account->accountIdentifier() . '/category-change-requests/' . $request->requestIdentifier() . '/', $snapshot->documents->firstOrFail()->document_path);
        $this->assertSame('original bytes', $disk->get($snapshot->documents->firstOrFail()->document_path));
    }

    public function testCopyFailureRemovesEarlierCopiesAndDoesNotSaveSnapshot(): void
    {
        [$request, $account] = $this->createRequest(true);

        try {
            $this->service()->capture($request, $account);
            $this->fail('Missing source must fail snapshot capture.');
        } catch (DocumentStorageFailedException) {
            $this->assertDatabaseMissing('account_category_change_request_documents', ['request_id' => (string) $request->requestIdentifier()]);
            $this->assertDatabaseHas('account_category_change_requests', ['id' => (string) $request->requestIdentifier(), 'account_email' => (string) $account->email()]);
        }
        // Existing transaction rolls back after application failure, removing copied files.
        DB::rollBack();
        DB::beginTransaction();
        $this->assertSame(['original.pdf'], Storage::disk('verification-documents')->allFiles());
    }

    public function testRollbackRemovesCopiesButPreservesOriginal(): void
    {
        [$request, $account] = $this->createRequest();
        $this->service()->capture($request, $account);
        DB::rollBack();
        DB::beginTransaction();
        $this->assertSame(['original.pdf'], Storage::disk('verification-documents')->allFiles());
        $this->assertDatabaseCount('account_category_change_request_documents', 0);
    }

    public function testDeletesTargetSnapshotsAndFilesAfterCommitAndRetainsThemOnRollback(): void
    {
        [$request, $account] = $this->createRequest();
        [$otherRequest, $otherAccount] = $this->createRequest();
        $service = $this->service();
        $service->capture($request, $account);
        $service->capture($otherRequest, $otherAccount);
        $snapshot = AccountCategoryChangeRequestModel::query()->with('documents')->findOrFail((string) $request->requestIdentifier());
        $otherSnapshot = AccountCategoryChangeRequestModel::query()->with('documents')->findOrFail((string) $otherRequest->requestIdentifier());
        $disk = Storage::disk('verification-documents');
        DB::beginTransaction();
        $service->deleteForAccount($account->accountIdentifier());
        DB::rollBack();
        $this->assertDatabaseHas('account_category_change_request_documents', ['request_id' => (string) $request->requestIdentifier()]);
        $this->assertTrue($disk->exists($snapshot->documents->firstOrFail()->document_path));
        $service->deleteForAccount($account->accountIdentifier());
        $this->assertTrue($disk->exists($snapshot->documents->firstOrFail()->document_path));
        $this->assertDatabaseMissing('account_category_change_request_documents', ['request_id' => (string) $request->requestIdentifier()]);
        $this->assertDatabaseHas('account_category_change_request_documents', ['request_id' => (string) $otherRequest->requestIdentifier()]);
        DB::commit();
        $this->assertFalse($disk->exists($snapshot->documents->firstOrFail()->document_path));
        $this->assertTrue($disk->exists($otherSnapshot->documents->firstOrFail()->document_path));
        $this->assertTrue($disk->exists('original.pdf'));
        DB::table('accounts')->whereIn('id', [(string) $account->accountIdentifier(), (string) $otherAccount->accountIdentifier()])->delete();
        $this->assertDatabaseMissing('account_category_change_requests', ['id' => (string) $request->requestIdentifier()]);
        $this->assertDatabaseMissing('account_category_change_requests', ['id' => (string) $otherRequest->requestIdentifier()]);
        $this->assertDatabaseMissing('account_category_change_request_documents', ['request_id' => (string) $otherRequest->requestIdentifier()]);
        DB::beginTransaction();
    }

    private function service(): AccountCategoryChangeRequestSnapshotServiceInterface
    {
        return $this->app()->make(AccountCategoryChangeRequestSnapshotServiceInterface::class);
    }

    /** @return array{AccountCategoryChangeRequest, Account} */
    private function createRequest(bool $missingDocument = false): array
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);
        DB::table('accounts')->where('id', (string) $accountIdentifier)->update([
            'phone' => '+819012345678', 'address_country_code' => 'JP', 'address_administrative_area_code' => '13',
            'address_postal_code' => '100-0001', 'address_locality' => '千代田区', 'address_line1' => '千代田1-1', 'address_line2' => '1F',
        ]);
        Storage::disk('verification-documents')->put('original.pdf', 'original bytes');
        DB::table('account_documents')->insert(['account_id' => (string) $accountIdentifier, 'document_type' => 'passport', 'document_path' => 'original.pdf', 'uploaded_at' => '2026-10-21 00:00:00']);
        if ($missingDocument) {
            DB::table('account_documents')->insert(['account_id' => (string) $accountIdentifier, 'document_type' => 'selfie', 'document_path' => 'missing.png', 'uploaded_at' => '2026-10-21 00:00:00']);
        }
        $account = $this->app()->make(AccountRepositoryInterface::class)->findById($accountIdentifier);
        self::assertNotNull($account);
        $request = $this->app()->make(AccountCategoryChangeRequestFactoryInterface::class)->create($accountIdentifier, AccountCategory::GENERAL, AccountCategory::TALENT, AccountCategoryChangeRequestAccount::fromAccount($account));
        $this->app()->make(AccountCategoryChangeRequestRepositoryInterface::class)->save($request);

        return [$request, $account];
    }
}
