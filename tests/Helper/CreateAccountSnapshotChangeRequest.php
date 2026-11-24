<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Assert;
use Source\Account\Account\Application\UseCase\Command\RejectAccountSnapshotChangeRequest\RejectAccountSnapshotChangeRequestInput;
use Source\Account\Account\Application\UseCase\Command\RejectAccountSnapshotChangeRequest\RejectAccountSnapshotChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Command\RejectAccountSnapshotChangeRequest\RejectAccountSnapshotChangeRequestOutput;
use Source\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange\RequestAccountSnapshotChangeInput;
use Source\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange\RequestAccountSnapshotChangeInterface;
use Source\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange\RequestAccountSnapshotChangeOutput;
use Source\Account\Account\Application\UseCase\Query\AccountSnapshotChangeRequestReadModel;
use Source\Account\Account\Application\UseCase\Query\AccountSnapshotReadModel;
use Source\Account\Account\Domain\Entity\AccountSnapshotChangeRequest;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeReason;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Account\Account\Domain\ValueObject\RejectionReasonCode;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class CreateAccountSnapshotChangeRequest
{
    public static function prepare(): AccountIdentifier
    {
        Storage::fake('verification-documents');
        $policy = Mockery::mock(PolicyEvaluatorInterface::class);
        $policy->shouldReceive('evaluate')->andReturn(true);
        app()->instance(PolicyEvaluatorInterface::class, $policy);
        $id = CreateAccountSnapshot::account();
        CreateAccountSnapshot::save($id);

        return $id;
    }

    public static function entity(): AccountSnapshotChangeRequest
    {
        return new AccountSnapshotChangeRequest(
            new AccountSnapshotChangeRequestIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountSnapshotIdentifier(StrTestHelper::generateUuid()),
            new AccountSnapshotChangeReason('住所変更'),
            new DateTimeImmutable('2026-11-25'),
            AccountSnapshotChangeRequestStatus::PENDING,
            null,
            null,
            null,
            null,
        );
    }

    public static function readModel(): AccountSnapshotChangeRequestReadModel
    {
        $entity = self::entity();
        $output = new RequestAccountSnapshotChangeOutput();
        $output->setRequest($entity);
        $summary = $output->toArray();
        if ($summary === []) {
            Assert::fail('Expected populated summary.');
        }
        $original = new AccountSnapshotReadModel((string) $entity->snapshotIdentifier(), (string) $entity->accountIdentifier(), 'approved', '2026-01-01T00:00:00+00:00', true, ['name' => 'Original'], [['documentType' => 'passport','documentPath' => 'private/original.pdf','uploadedAt' => '2026-01-01T00:00:00+00:00']]);

        return new AccountSnapshotChangeRequestReadModel($summary, $original, ['name' => 'Proposed'], [['documentType' => 'passport','documentPath' => 'private/proposal.pdf','uploadedAt' => '2026-01-01T00:00:00+00:00']]);
    }

    public static function create(AccountIdentifier $id): AccountSnapshotChangeRequestIdentifier
    {
        DB::table('accounts')->where('id', (string) $id)->update(['name' => 'Proposed name']);
        $output = new RequestAccountSnapshotChangeOutput();
        app(RequestAccountSnapshotChangeInterface::class)->process(new RequestAccountSnapshotChangeInput($id, CreateAccountSnapshot::principal($id), new AccountSnapshotChangeReason('改名')), $output);

        $data = $output->toArray();
        if ($data === []) {
            Assert::fail('Expected populated output.');
        }

        return new AccountSnapshotChangeRequestIdentifier($data['requestIdentifier']);
    }

    public static function reject(AccountSnapshotChangeRequestIdentifier $requestIdentifier, AccountIdentifier $accountIdentifier): void
    {
        app(RejectAccountSnapshotChangeRequestInterface::class)->process(
            new RejectAccountSnapshotChangeRequestInput($requestIdentifier, CreateAccountSnapshot::principal($accountIdentifier), new RejectionReason(RejectionReasonCode::OTHER, '再提出')),
            new RejectAccountSnapshotChangeRequestOutput(),
        );
    }
}
