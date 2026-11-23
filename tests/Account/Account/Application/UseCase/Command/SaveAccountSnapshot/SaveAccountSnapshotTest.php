<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\SaveAccountSnapshot;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Account\Account\Application\Exception\AccountSnapshotForbiddenException;
use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Application\Exception\IncompleteAccountContactForCategoryChangeException;
use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Application\Service\DocumentStorageServiceInterface;
use Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot\SaveAccountSnapshot;
use Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot\SaveAccountSnapshotInput;
use Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot\SaveAccountSnapshotInterface;
use Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot\SaveAccountSnapshotOutput;
use Source\Account\Account\Domain\Entity\AccountSnapshot;
use Source\Account\Account\Domain\Exception\AccountSetupUnavailableException;
use Source\Account\Account\Domain\Repository\AccountSnapshotRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateAccountSnapshot;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;
use Throwable;

#[Group('useDb')]
class SaveAccountSnapshotTest extends TestCase
{
    private function prepareSnapshotAccount(): AccountIdentifier
    {
        Storage::fake('verification-documents');
        $policy = $this->createStub(PolicyEvaluatorInterface::class);
        $policy->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policy);

        return CreateAccountSnapshot::account();
    }

    /** @param class-string<Throwable> $exception */
    #[DataProvider('invalidData')]
    public function testInvalidDataIsRejectedWithoutSnapshotWritesOrCopies(string $field, string $exception): void
    {
        $id = $this->prepareSnapshotAccount();
        if ($field === 'documents') {
            DB::table('account_documents')->where('account_id', (string) $id)->where('document_type', 'passport')->delete();
        } else {
            DB::table('accounts')->where('id', (string) $id)->update([$field => null]);
        }
        $this->expectException($exception);

        try {
            CreateAccountSnapshot::save($id);
        } finally {
            self::assertDatabaseCount('account_snapshots', 0);
            self::assertSame([], Storage::disk('verification-documents')->allFiles('accounts/' . $id . '/snapshots'));
        }
    }

    /** @return list<array{string, class-string<Throwable>}> */
    public static function invalidData(): array
    {
        return [
            ['phone', IncompleteAccountContactForCategoryChangeException::class],
            ['address_line1', IncompleteAccountContactForCategoryChangeException::class],
            ['type', AccountSetupUnavailableException::class],
            ['documents', InvalidDocumentsForVerificationException::class],
        ];
    }

    public function testReuploadWithIdenticalBytesDoesNotCreateVersion(): void
    {
        $id = $this->prepareSnapshotAccount();
        $first = CreateAccountSnapshot::save($id);
        Storage::disk('verification-documents')->put('reuploaded.pdf', 'passport bytes');
        DB::table('account_documents')->where('account_id', (string) $id)->where('document_type', 'passport')->update(['document_path' => 'reuploaded.pdf', 'uploaded_at' => '2026-04-01']);
        $second = CreateAccountSnapshot::save($id);
        self::assertEquals($first, $second);
        self::assertCount(2, Storage::disk('verification-documents')->allFiles('accounts/' . $id . '/snapshots'));
    }

    #[DataProvider('changes')]
    public function testChangedContentsCreateIndependentVersion(string $change): void
    {
        $id = $this->prepareSnapshotAccount();
        $disk = Storage::disk('verification-documents');
        $disk->put('driver.pdf', 'driver bytes');
        DB::table('account_documents')->insert(['account_id' => (string) $id, 'document_type' => 'driver_license', 'document_path' => 'driver.pdf', 'uploaded_at' => '2026-01-01']);
        $first = CreateAccountSnapshot::save($id);
        if ($change === 'account') {
            DB::table('accounts')->where('id', (string) $id)->update(['name' => 'New name']);
        } elseif ($change === 'replace') {
            $disk->put((string) $id . '/passport.pdf', 'replacement bytes');
        } elseif ($change === 'delete') {
            DB::table('account_documents')->where('account_id', (string) $id)->where('document_type', 'driver_license')->delete();
        } else {
            $disk->put('registration.pdf', 'registration bytes');
            DB::table('account_documents')->insert(['account_id' => (string) $id, 'document_type' => 'resident_registration', 'document_path' => 'registration.pdf', 'uploaded_at' => '2026-01-01']);
        }
        $second = CreateAccountSnapshot::save($id);
        self::assertSame(2, $second->version());
        self::assertNotSame((string) $first->snapshotIdentifier(), (string) $second->snapshotIdentifier());
        self::assertSame('passport bytes', $disk->get((string) $first->documents()[1]->documentPath()));
        self::assertSame('Test Account', $this->app()->make(AccountSnapshotRepositoryInterface::class)->findById($first->snapshotIdentifier())?->account()->name());
    }

    /** @return list<array{string}> */
    public static function changes(): array
    {
        return [['account'], ['replace'], ['delete'], ['add']];
    }

    public function testForeignActorIsRejectedBeforeAccountRead(): void
    {
        $id = $this->prepareSnapshotAccount();
        $other = new AccountIdentifier(StrTestHelper::generateUuid());
        $this->expectException(AccountSnapshotForbiddenException::class);
        $this->app()->make(SaveAccountSnapshotInterface::class)->process(new SaveAccountSnapshotInput($id, CreateAccountSnapshot::principal($other)), new SaveAccountSnapshotOutput());
    }

    public function testDeniedPolicyRejectsSave(): void
    {
        $id = $this->prepareSnapshotAccount();
        $policy = $this->createStub(PolicyEvaluatorInterface::class);
        $policy->method('evaluate')->willReturn(false);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policy);
        $this->expectException(AccountSnapshotForbiddenException::class);
        CreateAccountSnapshot::save($id);
    }

    public function testCopyFailureRemovesEarlierCopiesOnRollback(): void
    {
        $id = $this->prepareSnapshotAccount();
        $real = $this->app()->make(DocumentStorageServiceInterface::class);
        $storage = $this->createMock(DocumentStorageServiceInterface::class);
        $storage->method('get')->willReturnCallback($real->get(...));
        $copies = 0;
        $storage->expects(self::exactly(2))->method('copy')->willReturnCallback(function (DocumentPath $source, DocumentPath $destination) use ($real, &$copies): void {
            if (++$copies === 2) {
                throw new RuntimeException('copy failed');
            }
            $real->copy($source, $destination);
        });
        $storage->method('delete')->willReturnCallback($real->delete(...));
        $this->app()->instance(DocumentStorageServiceInterface::class, $storage);
        DB::beginTransaction();

        try {
            CreateAccountSnapshot::save($id);
            self::fail('Expected copy failure.');
        } catch (DocumentStorageFailedException) {
            self::assertSame(2, $copies);
        } finally {
            DB::rollBack();
        }
        self::assertNull($this->app()->make(AccountSnapshotRepositoryInterface::class)->findCurrent($id));
        self::assertSame([], Storage::disk('verification-documents')->allFiles('accounts/' . $id . '/snapshots'));
    }

    public function testLatePersistenceFailureRollsBackWrittenVersionAndCopies(): void
    {
        $id = $this->prepareSnapshotAccount();
        $first = CreateAccountSnapshot::save($id);
        DB::table('accounts')->where('id', (string) $id)->update(['name' => 'Changed']);
        $real = $this->app()->make(AccountSnapshotRepositoryInterface::class);
        $repository = $this->createMock(AccountSnapshotRepositoryInterface::class);
        $repository->method('findCurrent')->willReturnCallback($real->findCurrent(...));
        $repository->expects(self::once())->method('save')->willReturnCallback(function (AccountSnapshot $snapshot) use ($real): void {
            $real->save($snapshot);
            self::assertDatabaseHas('account_snapshots', ['id' => (string) $snapshot->snapshotIdentifier()]);

            throw new RuntimeException('failure after real write');
        });
        $this->app()->instance(AccountSnapshotRepositoryInterface::class, $repository);
        $useCase = $this->app()->make(SaveAccountSnapshot::class);
        $files = Storage::disk('verification-documents')->allFiles();
        DB::beginTransaction();

        try {
            $useCase->process(new SaveAccountSnapshotInput($id, CreateAccountSnapshot::principal($id)), new SaveAccountSnapshotOutput());
            self::fail('Expected persistence failure.');
        } catch (RuntimeException $exception) {
            self::assertSame('failure after real write', $exception->getMessage());
        } finally {
            DB::rollBack();
        }
        self::assertSame((string) $first->snapshotIdentifier(), (string) $real->findCurrent($id)?->snapshotIdentifier());
        self::assertSame($files, Storage::disk('verification-documents')->allFiles());
        self::assertDatabaseCount('account_snapshots', 1);
    }

    public function testSameContentsReturnExistingVersionWithoutWritesOrCopies(): void
    {
        $id = StrTestHelper::generateUuid();
        CreateAccount::create($id);
        DB::table('accounts')->where('id', $id)->update(['phone' => '+819012345678', 'address_country_code' => 'FR', 'address_postal_code' => '75001', 'address_line1' => 'Paris']);
        $disk = Storage::fake('verification-documents');
        foreach (['passport', 'selfie'] as $type) {
            $disk->put($type . '.pdf', $type . ' bytes');
            DB::table('account_documents')->insert(['account_id' => $id, 'document_type' => $type, 'document_path' => $type . '.pdf', 'uploaded_at' => '2026-01-01']);
        }
        $policyEvaluator = $this->createStub(PolicyEvaluatorInterface::class);
        $policyEvaluator->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier($id));
        $input = new SaveAccountSnapshotInput(new AccountIdentifier($id), $principal);
        $service = $this->app()->make(SaveAccountSnapshotInterface::class);
        $first = new SaveAccountSnapshotOutput();
        $service->process($input, $first);
        self::assertSame(1, $first->toArray()['version']);
        $files = $disk->allFiles();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $second = new SaveAccountSnapshotOutput();
        $service->process($input, $second);
        $writes = array_filter(DB::getQueryLog(), static fn (array $query): bool => preg_match('/^(insert|update|delete)/i', $query['query']) === 1);
        DB::disableQueryLog();
        self::assertSame([], array_values($writes));
        self::assertSame($first->toArray(), $second->toArray());
        self::assertSame($files, $disk->allFiles());
    }
}
