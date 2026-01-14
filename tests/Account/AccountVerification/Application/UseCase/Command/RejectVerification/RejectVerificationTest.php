<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Application\UseCase\Command\RejectVerification;

use DateTimeImmutable;
use DomainException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\AccountVerification\Application\Exception\AccountVerificationNotFoundException;
use Source\Account\AccountVerification\Application\UseCase\Command\RejectVerification\RejectVerificationInput;
use Source\Account\AccountVerification\Application\UseCase\Command\RejectVerification\RejectVerificationInterface;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;
use Source\Account\AccountVerification\Domain\Repository\AccountVerificationRepositoryInterface;
use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\RejectionReason;
use Source\Account\AccountVerification\Domain\ValueObject\RejectionReasonCode;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationStatus;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectVerificationTest extends TestCase
{
    /**
     * 正常系: 本人認証を正しくRejectできること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());
        $reviewerAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $rejectionReason = new RejectionReason(RejectionReasonCode::DOCUMENT_UNCLEAR, 'Image is blurry');

        $verification = $this->createVerification($verificationId, $accountId, VerificationStatus::PENDING);

        $verificationRepository = Mockery::mock(AccountVerificationRepositoryInterface::class);
        $verificationRepository
            ->shouldReceive('findById')
            ->with($verificationId)
            ->once()
            ->andReturn($verification);
        $verificationRepository
            ->shouldReceive('save')
            ->once()
            ->andReturnNull();

        $input = new RejectVerificationInput($verificationId, $reviewerAccountId, $rejectionReason);

        $this->app->instance(AccountVerificationRepositoryInterface::class, $verificationRepository);
        $useCase = $this->app->make(RejectVerificationInterface::class);
        $result = $useCase->process($input);

        $this->assertTrue($result->isRejected());
        $this->assertSame(RejectionReasonCode::DOCUMENT_UNCLEAR, $result->rejectionReason()->code());
    }

    /**
     * 異常系: IDに紐づく本人認証が存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenVerificationNotFound(): void
    {
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());
        $reviewerAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $rejectionReason = new RejectionReason(RejectionReasonCode::DOCUMENT_UNCLEAR);

        $verificationRepository = Mockery::mock(AccountVerificationRepositoryInterface::class);
        $verificationRepository
            ->shouldReceive('findById')
            ->with($verificationId)
            ->once()
            ->andReturn(null);
        $verificationRepository->shouldNotReceive('save');

        $this->app->instance(AccountVerificationRepositoryInterface::class, $verificationRepository);
        $useCase = $this->app->make(RejectVerificationInterface::class);

        $this->expectException(AccountVerificationNotFoundException::class);

        $input = new RejectVerificationInput($verificationId, $reviewerAccountId, $rejectionReason);
        $useCase->process($input);
    }

    /**
     * 異常系: すでに却下されている場合に例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenAlreadyRejected(): void
    {
        $verificationId = new VerificationIdentifier(StrTestHelper::generateUuid());
        $reviewerAccountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $rejectionReason = new RejectionReason(RejectionReasonCode::DOCUMENT_UNCLEAR);

        $verification = $this->createVerification($verificationId, $accountId, VerificationStatus::REJECTED);

        $verificationRepository = Mockery::mock(AccountVerificationRepositoryInterface::class);
        $verificationRepository
            ->shouldReceive('findById')
            ->with($verificationId)
            ->once()
            ->andReturn($verification);
        $verificationRepository->shouldNotReceive('save');

        $this->app->instance(AccountVerificationRepositoryInterface::class, $verificationRepository);
        $useCase = $this->app->make(RejectVerificationInterface::class);

        $this->expectException(DomainException::class);

        $input = new RejectVerificationInput($verificationId, $reviewerAccountId, $rejectionReason);
        $useCase->process($input);
    }

    private function createVerification(
        VerificationIdentifier $verificationId,
        AccountIdentifier $accountId,
        VerificationStatus $status,
    ): AccountVerification {
        return new AccountVerification(
            $verificationId,
            $accountId,
            VerificationType::TALENT,
            $status,
            new ApplicantInfo('Taro Yamada'),
            new DateTimeImmutable(),
            null,
            null,
            null,
        );
    }
}
