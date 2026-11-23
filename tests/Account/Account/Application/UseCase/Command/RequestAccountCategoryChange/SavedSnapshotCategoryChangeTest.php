<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequiresApprovalException;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest\ApproveAccountCategoryChangeRequestInput;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest\ApproveAccountCategoryChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest\ApproveAccountCategoryChangeRequestOutput;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeInput;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeInterface;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeOutput;
use Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestInput;
use Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestOutput;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Tests\Helper\CreateAccountSnapshot;
use Tests\TestCase;

#[Group('useDb')]
class SavedSnapshotCategoryChangeTest extends TestCase
{
    public function testApprovedReferencePreventsChangedSaveButSameContentsRemainNoOp(): void
    {
        Storage::fake('verification-documents');
        $allow = $this->createStub(PolicyEvaluatorInterface::class);
        $allow->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $allow);
        $id = CreateAccountSnapshot::account();
        $first = CreateAccountSnapshot::save($id);
        $output = new RequestAccountCategoryChangeOutput();
        $this->app()->make(RequestAccountCategoryChangeInterface::class)->process(new RequestAccountCategoryChangeInput($id, CreateAccountSnapshot::principal($id), AccountCategory::AGENCY), $output);
        $payload = $output->toArray();
        self::assertTrue(array_key_exists('requestIdentifier', $payload));
        $requestId = $payload['requestIdentifier'];
        $this->app()->instance(EventDispatcherInterface::class, $this->createStub(EventDispatcherInterface::class));
        $this->app()->make(ApproveAccountCategoryChangeRequestInterface::class)->process(new ApproveAccountCategoryChangeRequestInput(new AccountCategoryChangeRequestIdentifier($requestId), CreateAccountSnapshot::principal($id)), new ApproveAccountCategoryChangeRequestOutput());
        self::assertDatabaseHas('account_category_change_requests', ['id' => $requestId, 'status' => 'approved', 'approved_snapshot_id' => (string) $first->snapshotIdentifier()]);
        self::assertSame((string) $first->snapshotIdentifier(), (string) CreateAccountSnapshot::save($id)->snapshotIdentifier());
        DB::table('accounts')->where('id', (string) $id)->update(['name' => 'Changed']);
        $this->expectException(AccountSnapshotChangeRequiresApprovalException::class);
        CreateAccountSnapshot::save($id);
    }

    public function testNewCurrentVersionDoesNotChangeApplicationReviewOrCopiedDocuments(): void
    {
        $disk = Storage::fake('verification-documents');
        $allow = $this->createStub(PolicyEvaluatorInterface::class);
        $allow->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $allow);
        $id = CreateAccountSnapshot::account();
        $first = CreateAccountSnapshot::save($id);
        $output = new RequestAccountCategoryChangeOutput();
        $this->app()->make(RequestAccountCategoryChangeInterface::class)->process(new RequestAccountCategoryChangeInput($id, CreateAccountSnapshot::principal($id), AccountCategory::AGENCY), $output);
        $payload = $output->toArray();
        self::assertTrue(array_key_exists('requestIdentifier', $payload));
        DB::table('accounts')->where('id', (string) $id)->update(['name' => 'New current name']);
        $disk->put($id.'/passport.pdf', 'new passport bytes');
        $second = CreateAccountSnapshot::save($id);
        self::assertSame(2, $second->version());
        $detail = new GetAccountCategoryChangeRequestOutput();
        $this->app()->make(GetAccountCategoryChangeRequestInterface::class)->process(new GetAccountCategoryChangeRequestInput(new AccountCategoryChangeRequestIdentifier($payload['requestIdentifier']), CreateAccountSnapshot::principal(CreateAccountSnapshot::account())), $detail);
        $review = $detail->toArray();
        self::assertTrue(array_key_exists('request', $review));
        self::assertTrue(array_key_exists('snapshotIdentifier', $review['request']));
        self::assertSame((string) $first->snapshotIdentifier(), $review['request']['snapshotIdentifier']);
        self::assertSame($first->account()->name(), $review['account']['name']);
        foreach ($review['documents'] as $document) {
            self::assertSame($document['documentType'].' bytes', $disk->get($document['documentPath']));
        }
        self::assertNotSame($first->snapshotIdentifier(), $second->snapshotIdentifier());
    }

    /** @return iterable<string, array{string}> */
    public static function unusedStates(): iterable
    {
        yield 'pending' => ['pending'];
        yield 'rejected' => ['rejected'];
        yield 'withdrawn' => ['withdrawn'];
    }

    #[DataProvider('unusedStates')]
    public function testUnapprovedHistoryDoesNotBlockChangedSave(string $status): void
    {
        Storage::fake('verification-documents');
        $allow = $this->createStub(PolicyEvaluatorInterface::class);
        $allow->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $allow);
        $id = CreateAccountSnapshot::account();
        CreateAccountSnapshot::save($id);
        $output = new RequestAccountCategoryChangeOutput();
        $this->app()->make(RequestAccountCategoryChangeInterface::class)->process(new RequestAccountCategoryChangeInput($id, CreateAccountSnapshot::principal($id), AccountCategory::AGENCY), $output);
        DB::table('account_category_change_requests')->where('account_id', (string) $id)->update(['status' => $status]);
        DB::table('accounts')->where('id', (string) $id)->update(['name' => 'Allowed new contents']);
        self::assertSame(2, CreateAccountSnapshot::save($id)->version());
    }

    public function testRequestUsesSavedVersionWithoutRevalidatingLiveProfileOrCopyingDocuments(): void
    {
        Storage::fake('verification-documents');
        $allow = $this->createStub(PolicyEvaluatorInterface::class);
        $allow->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $allow);
        $id = CreateAccountSnapshot::account();
        $snapshot = CreateAccountSnapshot::save($id);
        DB::table('accounts')->where('id', (string) $id)->update(['phone' => null, 'address_line1' => null]);
        DB::table('account_documents')->where('account_id', (string) $id)->delete();
        $files = Storage::disk('verification-documents')->allFiles();
        $output = new RequestAccountCategoryChangeOutput();
        $this->app()->make(RequestAccountCategoryChangeInterface::class)->process(new RequestAccountCategoryChangeInput($id, CreateAccountSnapshot::principal($id), AccountCategory::AGENCY), $output);
        $payload = $output->toArray();
        self::assertTrue(array_key_exists('snapshotIdentifier', $payload));
        self::assertSame((string) $snapshot->snapshotIdentifier(), $payload['snapshotIdentifier']);
        self::assertSame($files, Storage::disk('verification-documents')->allFiles());
        self::assertDatabaseCount('account_snapshots', 1);
    }
}
