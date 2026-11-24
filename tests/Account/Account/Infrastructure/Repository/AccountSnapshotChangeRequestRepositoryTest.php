<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Domain\Repository\AccountSnapshotChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Tests\Helper\CreateAccountSnapshotChangeRequest;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class AccountSnapshotChangeRequestRepositoryTest extends TestCase
{
    public function testRoundTripReviewAndImmutableDocumentsAndAccountStatusFiltering(): void
    {
        $id = CreateAccountSnapshotChangeRequest::prepare();
        $requestId = CreateAccountSnapshotChangeRequest::create($id);
        $repository = $this->app()->make(AccountSnapshotChangeRequestRepositoryInterface::class);
        $request = $repository->findById($requestId);
        self::assertNotNull($request);
        self::assertCount(1, $repository->findByAccountIdAndStatuses($id, [AccountSnapshotChangeRequestStatus::PENDING]));
        $approved = new AccountSnapshotIdentifier(StrTestHelper::generateUuid());
        $request->approve($id, $approved);
        $repository->save($request);
        self::assertEquals($request, $repository->findById($requestId));
        self::assertSame([], $repository->findByAccountIdAndStatuses($id, [AccountSnapshotChangeRequestStatus::PENDING]));
        self::assertNull($repository->findById(new AccountSnapshotChangeRequestIdentifier(StrTestHelper::generateUuid())));
    }
}
