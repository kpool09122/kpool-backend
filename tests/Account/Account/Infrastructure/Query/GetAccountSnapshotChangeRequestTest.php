<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Query;

use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestInput;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestOutput;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Tests\Helper\CreateAccountSnapshot;
use Tests\Helper\CreateAccountSnapshotChangeRequest;
use Tests\TestCase;

#[Group('useDb')]
class GetAccountSnapshotChangeRequestTest extends TestCase
{
    public function testOwnerReadsOriginalAndFrozenProposalWithoutExposingPaths(): void
    {
        Storage::fake('verification-documents');
        $policy = $this->createStub(PolicyEvaluatorInterface::class);
        $policy->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policy);
        $id = CreateAccountSnapshot::account();
        CreateAccountSnapshot::save($id);
        $requestId = CreateAccountSnapshotChangeRequest::create($id);
        $policy = $this->createMock(PolicyEvaluatorInterface::class);
        $policy->expects(self::once())->method('evaluate')->with(self::anything(), Action::READ, self::anything())->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policy);
        $output = new GetAccountSnapshotChangeRequestOutput();
        $this->app()->make(GetAccountSnapshotChangeRequestInterface::class)->process(new GetAccountSnapshotChangeRequestInput($requestId, CreateAccountSnapshot::principal($id)), $output);
        $data = $output->toArray();
        if ($data === []) {
            self::fail('Expected populated output.');
        }
        self::assertSame('Test Account', $data['original']['account']['name']);
        self::assertSame('Proposed name', $data['proposal']['account']['name']);
        self::assertCount(2, $data['proposal']['documents']);
        self::assertStringNotContainsString('documentPath', json_encode($data, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('contentHash', json_encode($data, JSON_THROW_ON_ERROR));
    }

    public function testForeignActorWithoutManagementPermissionCannotReadRequest(): void
    {
        Storage::fake('verification-documents');
        $policy = $this->createStub(PolicyEvaluatorInterface::class);
        $policy->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policy);
        $id = CreateAccountSnapshot::account();
        CreateAccountSnapshot::save($id);
        $requestId = CreateAccountSnapshotChangeRequest::create($id);
        $other = CreateAccountSnapshot::account();
        $policy = $this->createMock(PolicyEvaluatorInterface::class);
        $policy->expects(self::once())->method('evaluate')->with(self::anything(), Action::ACCOUNT_SNAPSHOT_CHANGE_REQUEST_MANAGE, self::anything())->willReturn(false);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policy);
        $this->expectException(AccountSnapshotChangeRequestForbiddenException::class);
        $this->app()->make(GetAccountSnapshotChangeRequestInterface::class)->process(new GetAccountSnapshotChangeRequestInput($requestId, CreateAccountSnapshot::principal($other)), new GetAccountSnapshotChangeRequestOutput());
    }
}
