<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountSnapshotForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotNotFoundException;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshot\GetAccountSnapshotInput;
use Source\Account\Account\Infrastructure\Query\GetAccountSnapshot;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccountSnapshot;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class GetAccountSnapshotTest extends TestCase
{
    public function testForeignVersionIdentifierIsNotFoundForOwner(): void
    {
        Storage::fake('verification-documents');
        $allow = $this->createStub(PolicyEvaluatorInterface::class);
        $allow->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $allow);
        $id = CreateAccountSnapshot::account();
        $other = CreateAccountSnapshot::account();
        $snapshot = CreateAccountSnapshot::save($other);
        $this->expectException(AccountSnapshotNotFoundException::class);
        (new GetAccountSnapshot($allow))->process(new GetAccountSnapshotInput($id, CreateAccountSnapshot::principal($id), $snapshot->snapshotIdentifier()));
    }

    public function testMissingCurrentVersionIsNotFound(): void
    {
        $allow = $this->createStub(PolicyEvaluatorInterface::class);
        $allow->method('evaluate')->willReturn(true);
        $id = new AccountIdentifier(StrTestHelper::generateUuid());
        $this->expectException(AccountSnapshotNotFoundException::class);
        (new GetAccountSnapshot($allow))->process(new GetAccountSnapshotInput($id, CreateAccountSnapshot::principal($id)));
    }

    public function testReadOnlyOwnerCanReadSnapshot(): void
    {
        Storage::fake('verification-documents');
        $allow = $this->createStub(PolicyEvaluatorInterface::class);
        $allow->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $allow);
        $id = CreateAccountSnapshot::account();
        $snapshot = CreateAccountSnapshot::save($id);
        $policy = $this->createStub(PolicyEvaluatorInterface::class);
        $policy->method('evaluate')->willReturnCallback(static fn ($principal, Action $action): bool => $action === Action::READ);
        $result = (new GetAccountSnapshot($policy))->process(new GetAccountSnapshotInput($id, CreateAccountSnapshot::principal($id), $snapshot->snapshotIdentifier()));
        self::assertSame((string)$snapshot->snapshotIdentifier(), $result->snapshotIdentifier());
    }

    public function testAuthorizedOperationsReaderCanReadAndPathsStayPrivate(): void
    {
        Storage::fake('verification-documents');
        $allow = $this->createStub(PolicyEvaluatorInterface::class);
        $allow->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $allow);
        $id = CreateAccountSnapshot::account();
        $snapshot = CreateAccountSnapshot::save($id);
        $actor = CreateAccountSnapshot::principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $policy = $this->createMock(PolicyEvaluatorInterface::class);
        $policy->expects(self::once())->method('evaluate')->with($actor, Action::ACCOUNT_CATEGORY_CHANGE_REQUEST_MANAGE, self::anything())->willReturn(true);
        $result = (new GetAccountSnapshot($policy))->process(new GetAccountSnapshotInput($id, $actor, $snapshot->snapshotIdentifier()));
        self::assertSame((string)$snapshot->snapshotIdentifier(), $result->snapshotIdentifier());
        self::assertSame(['passport', 'selfie'], array_column($result->toArray()['documents'], 'documentType'));
        self::assertArrayNotHasKey('documentPath', $result->toArray()['documents'][0]);
    }

    public function testSpecificPriorVersionRemainsReadableAndIsNotCurrent(): void
    {
        Storage::fake('verification-documents');
        $allow = $this->createStub(PolicyEvaluatorInterface::class);
        $allow->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $allow);
        $id = CreateAccountSnapshot::account();
        $first = CreateAccountSnapshot::save($id);
        DB::table('accounts')->where('id', (string) $id)->update(['name' => 'Changed']);
        $second = CreateAccountSnapshot::save($id);
        DB::table('account_snapshots')->where('id', (string)$first->snapshotIdentifier())->update(['saved_at' => '2000-01-01']);
        $other = CreateAccountSnapshot::save(CreateAccountSnapshot::account());
        DB::table('account_snapshots')->where('id', (string)$other->snapshotIdentifier())->update(['saved_at' => '2099-01-01']);
        $query = new GetAccountSnapshot($allow);
        $old = $query->process(new GetAccountSnapshotInput($id, CreateAccountSnapshot::principal($id), $first->snapshotIdentifier()));
        $current = $query->process(new GetAccountSnapshotInput($id, CreateAccountSnapshot::principal($id)));
        self::assertFalse($old->isCurrent());
        self::assertSame('Test Account', $old->account()['name']);
        self::assertSame((string) $second->snapshotIdentifier(), $current->snapshotIdentifier());
        self::assertTrue($current->isCurrent());
        $specific = $query->process(new GetAccountSnapshotInput($id, CreateAccountSnapshot::principal($id), $second->snapshotIdentifier()));
        self::assertTrue($specific->isCurrent());
    }

    public function testForeignReaderNeedsOperationsPermissionBeforeReadingAnySnapshot(): void
    {
        $actor = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
        $policy = $this->createMock(PolicyEvaluatorInterface::class);
        $policy->expects(self::once())->method('evaluate')->with($actor, Action::ACCOUNT_CATEGORY_CHANGE_REQUEST_MANAGE, self::anything())->willReturn(false);
        $query = new GetAccountSnapshot($policy);
        $this->expectException(AccountSnapshotForbiddenException::class);
        $query->process(new GetAccountSnapshotInput(new AccountIdentifier(StrTestHelper::generateUuid()), $actor));
    }
}
