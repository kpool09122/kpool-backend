<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Repository;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Domain\Repository\AccountSnapshotRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Tests\Helper\CreateAccountSnapshot;
use Tests\TestCase;

#[Group('useDb')]
class AccountSnapshotRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('verification-documents');
        $policy = $this->createStub(PolicyEvaluatorInterface::class);
        $policy->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policy);
    }

    public function testRoundTripsAllDataAndKeepsPriorVersionAndDocuments(): void
    {
        $id = CreateAccountSnapshot::account();
        $first = CreateAccountSnapshot::save($id);
        $repository = $this->app()->make(AccountSnapshotRepositoryInterface::class);
        $roundtrip = $repository->findById($first->snapshotIdentifier());
        self::assertNotNull($roundtrip);
        self::assertEquals($first, $roundtrip);
        DB::table('accounts')->where('id', (string)$id)->update(['name' => 'Changed']);
        $second = CreateAccountSnapshot::save($id);
        self::assertSame(2, $second->version());
        self::assertNotSame((string)$first->snapshotIdentifier(), (string)$second->snapshotIdentifier());
        self::assertSame('Test Account', $repository->findById($first->snapshotIdentifier())?->account()->name());
        self::assertSame('Changed', $repository->findCurrent($id)?->account()->name());
        self::assertSame('passport bytes', Storage::disk('verification-documents')->get((string)$first->documents()[0]->documentPath()));
        $other = CreateAccountSnapshot::account();
        self::assertNull($repository->findCurrent($other));
    }

    public function testCurrentVersionIsScopedToAccountAndUsesVersionRatherThanSaveTime(): void
    {
        $id = CreateAccountSnapshot::account();
        $repository = $this->app()->make(AccountSnapshotRepositoryInterface::class);
        $first = CreateAccountSnapshot::save($id);
        DB::table('account_snapshots')->where('id', (string)$first->snapshotIdentifier())->update(['version' => 5, 'saved_at' => '2099-01-01']);
        DB::table('accounts')->where('id', (string)$id)->update(['name' => 'Changed']);
        $second = CreateAccountSnapshot::save($id);
        $other = CreateAccountSnapshot::save(CreateAccountSnapshot::account());
        DB::table('account_snapshots')->where('id', (string)$other->snapshotIdentifier())->update(['version' => 50]);

        self::assertSame(6, $second->version());
        $current = $repository->findCurrent($id);
        self::assertNotNull($current);
        self::assertSame((string)$second->snapshotIdentifier(), (string)$current->snapshotIdentifier());
        self::assertSame(5, $repository->findById($first->snapshotIdentifier())?->version());
    }
}
