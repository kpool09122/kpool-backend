<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountDocumentUploadForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotForbiddenException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Account\Application\Exception\IncompleteAccountContactForCategoryChangeException;
use Source\Account\Account\Application\Exception\InvalidAccountSnapshotChangeRequestException;
use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Application\Exception\NoAccountSnapshotChangesException;
use Source\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange\RequestAccountSnapshotChangeInput;
use Source\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange\RequestAccountSnapshotChangeInterface;
use Source\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange\RequestAccountSnapshotChangeOutput;
use Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot\SaveAccountSnapshotInput;
use Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot\SaveAccountSnapshotInterface;
use Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot\SaveAccountSnapshotOutput;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountInput;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountInterface;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountOutput;
use Source\Account\Account\Application\UseCase\Command\UploadDocuments\UploadDocumentsInput;
use Source\Account\Account\Application\UseCase\Command\UploadDocuments\UploadDocumentsInterface;
use Source\Account\Account\Application\UseCase\Command\UploadDocuments\UploadDocumentsOutput;
use Source\Account\Account\Domain\Repository\AccountSnapshotChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountSnapshotRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeReason;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestStatus;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccountSnapshot;
use Tests\TestCase;

#[Group('useDb')]
class RequestAccountSnapshotChangeTest extends TestCase
{
    private function prepare(): AccountIdentifier
    {
        Storage::fake('verification-documents');
        $policyEvaluator = $this->createStub(PolicyEvaluatorInterface::class);
        $policyEvaluator->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $id = CreateAccountSnapshot::account();
        CreateAccountSnapshot::save($id);

        return $id;
    }

    public function testSubmissionValidatesProposalWithoutCopyingDocuments(): void
    {
        $id = $this->prepare();
        $current = $this->app()->make(AccountSnapshotRepositoryInterface::class)->findCurrent($id);
        DB::table('accounts')->where('id', (string) $id)->update(['name' => 'Proposed name']);
        $files = Storage::disk('verification-documents')->allFiles();
        $principal = CreateAccountSnapshot::principal($id);
        $output = new RequestAccountSnapshotChangeOutput();
        $this->app()->make(RequestAccountSnapshotChangeInterface::class)->process(new RequestAccountSnapshotChangeInput($id, $principal, new AccountSnapshotChangeReason('改名')), $output);
        $data = $output->toArray();
        if ($data === []) {
            self::fail('Expected populated output.');
        }
        self::assertSame('pending', $data['status']);
        self::assertSame('改名', $data['changeReason']);
        self::assertSame((string) $current?->snapshotIdentifier(), $data['snapshotIdentifier']);
        self::assertNull($data['reviewedSnapshotIdentifier']);
        self::assertDatabaseCount('account_snapshots', 1);
        $request = $this->app()->make(AccountSnapshotChangeRequestRepositoryInterface::class)->findByAccountIdAndStatuses($id, [AccountSnapshotChangeRequestStatus::PENDING])[0];
        self::assertSame($data['requestIdentifier'], (string) $request->requestIdentifier());
        self::assertSame($files, Storage::disk('verification-documents')->allFiles());
    }

    public function testUnchangedContentsCannotSubmit(): void
    {
        $id = $this->prepare();
        $files = Storage::disk('verification-documents')->allFiles();

        try {
            $this->expectException(NoAccountSnapshotChangesException::class);
            $this->app()->make(RequestAccountSnapshotChangeInterface::class)->process(
                new RequestAccountSnapshotChangeInput($id, CreateAccountSnapshot::principal($id), new AccountSnapshotChangeReason('変更なし')),
                new RequestAccountSnapshotChangeOutput(),
            );
        } finally {
            self::assertDatabaseCount('account_snapshot_change_requests', 0);
            self::assertDatabaseCount('account_snapshots', 1);
            self::assertSame($files, Storage::disk('verification-documents')->allFiles());
        }
    }

    public function testIdenticalDocumentBytesAtNewPathCannotSubmit(): void
    {
        $id = $this->prepare();
        Storage::disk('verification-documents')->put((string) $id.'/replacement.pdf', 'passport bytes');
        DB::table('account_documents')->where('account_id', (string) $id)->where('document_type', 'passport')->update([
            'document_path' => (string) $id.'/replacement.pdf',
            'uploaded_at' => now(),
        ]);
        $this->expectException(NoAccountSnapshotChangesException::class);
        $this->app()->make(RequestAccountSnapshotChangeInterface::class)->process(
            new RequestAccountSnapshotChangeInput($id, CreateAccountSnapshot::principal($id), new AccountSnapshotChangeReason('再アップロード')),
            new RequestAccountSnapshotChangeOutput(),
        );
    }

    public function testChangedDocumentBytesCanSubmitWithoutProfileChange(): void
    {
        $id = $this->prepare();
        Storage::disk('verification-documents')->put((string) $id.'/passport.pdf', 'changed passport bytes');
        $output = new RequestAccountSnapshotChangeOutput();
        $this->app()->make(RequestAccountSnapshotChangeInterface::class)->process(
            new RequestAccountSnapshotChangeInput($id, CreateAccountSnapshot::principal($id), new AccountSnapshotChangeReason('書類更新')),
            $output,
        );
        self::assertSame('pending', $output->toArray()['status'] ?? null);
        self::assertDatabaseCount('account_snapshot_change_requests', 1);
    }

    /** @return array<string, array{string}> */
    public static function blockedUpdates(): array
    {
        return ['profile' => ['profile'], 'documents' => ['documents'], 'snapshot' => ['snapshot']];
    }

    #[DataProvider('blockedUpdates')]
    public function testPendingRequestBlocksEditsAndNormalSnapshotSave(string $operation): void
    {
        $id = $this->prepare();
        DB::table('accounts')->where('id', (string) $id)->update(['name' => 'Proposed name']);
        $principal = CreateAccountSnapshot::principal($id);
        $this->app()->make(RequestAccountSnapshotChangeInterface::class)->process(
            new RequestAccountSnapshotChangeInput($id, $principal, new AccountSnapshotChangeReason('変更')),
            new RequestAccountSnapshotChangeOutput(),
        );
        $files = Storage::disk('verification-documents')->allFiles();

        try {
            if ($operation === 'profile') {
                $this->expectException(AccountUpdateForbiddenException::class);
                $this->app()->make(UpdateAccountInterface::class)->process(new UpdateAccountInput($id, $principal, new AccountName('Blocked edit')), new UpdateAccountOutput());
            } elseif ($operation === 'documents') {
                $this->expectException(AccountDocumentUploadForbiddenException::class);
                $this->app()->make(UploadDocumentsInterface::class)->process(new UploadDocumentsInput($id, $principal, []), new UploadDocumentsOutput());
            } else {
                $this->expectException(AccountSnapshotForbiddenException::class);
                $this->app()->make(SaveAccountSnapshotInterface::class)->process(new SaveAccountSnapshotInput($id, $principal), new SaveAccountSnapshotOutput());
            }
        } finally {
            self::assertSame($files, Storage::disk('verification-documents')->allFiles());
            self::assertDatabaseHas('accounts', ['id' => (string) $id, 'name' => 'Proposed name']);
            self::assertDatabaseCount('account_snapshots', 1);
        }
    }

    public function testDuplicatePendingRequestIsRejected(): void
    {
        $id = $this->prepare();
        DB::table('accounts')->where('id', (string) $id)->update(['name' => 'Proposed name']);
        $input = new RequestAccountSnapshotChangeInput($id, CreateAccountSnapshot::principal($id), new AccountSnapshotChangeReason('住所変更'));
        $useCase = $this->app()->make(RequestAccountSnapshotChangeInterface::class);
        $useCase->process($input, new RequestAccountSnapshotChangeOutput());
        $this->expectException(InvalidAccountSnapshotChangeRequestException::class);
        $useCase->process($input, new RequestAccountSnapshotChangeOutput());
    }

    public function testForeignAccountCannotSubmit(): void
    {
        $id = $this->prepare();
        $other = CreateAccountSnapshot::account();
        $this->expectException(AccountSnapshotChangeRequestForbiddenException::class);
        $this->app()->make(RequestAccountSnapshotChangeInterface::class)->process(new RequestAccountSnapshotChangeInput($id, CreateAccountSnapshot::principal($other), new AccountSnapshotChangeReason('変更')), new RequestAccountSnapshotChangeOutput());
    }

    public function testIncompleteContactCannotSubmit(): void
    {
        $id = $this->prepare();
        DB::table('accounts')->where('id', (string) $id)->update(['phone' => null]);
        $this->expectException(IncompleteAccountContactForCategoryChangeException::class);
        $this->app()->make(RequestAccountSnapshotChangeInterface::class)->process(new RequestAccountSnapshotChangeInput($id, CreateAccountSnapshot::principal($id), new AccountSnapshotChangeReason('変更')), new RequestAccountSnapshotChangeOutput());
    }

    public function testMissingRequiredDocumentCannotSubmit(): void
    {
        $id = $this->prepare();
        DB::table('account_documents')->where('account_id', (string) $id)->where('document_type', 'passport')->delete();
        $this->expectException(InvalidDocumentsForVerificationException::class);
        $this->app()->make(RequestAccountSnapshotChangeInterface::class)->process(new RequestAccountSnapshotChangeInput($id, CreateAccountSnapshot::principal($id), new AccountSnapshotChangeReason('変更')), new RequestAccountSnapshotChangeOutput());
    }
}
