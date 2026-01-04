<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\RejectAffiliation;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Application\Exception\AffiliationNotFoundException;
use Source\Account\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Application\UseCase\Command\RejectAffiliation\RejectAffiliation;
use Source\Account\Application\UseCase\Command\RejectAffiliation\RejectAffiliationInput;
use Source\Account\Application\UseCase\Command\RejectAffiliation\RejectAffiliationInterface;
use Source\Account\Domain\Entity\AccountAffiliation;
use Source\Account\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\AffiliationStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectAffiliationTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $useCase = $this->app->make(RejectAffiliationInterface::class);
        $this->assertInstanceOf(RejectAffiliation::class, $useCase);
    }

    /**
     * 正常系: 承認者がPENDINGのアフィリエーションを拒否できること.
     *
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        // 事務所からリクエストされた場合、タレントが承認者
        $affiliation = new AccountAffiliation(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $agencyAccountIdentifier, // requestedBy
            AffiliationStatus::PENDING,
            null,
            new DateTimeImmutable(),
            null,
            null,
        );

        $input = new RejectAffiliationInput(
            $affiliationIdentifier,
            $talentAccountIdentifier, // タレントが拒否
        );

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $affiliationIdentifier))
            ->andReturn($affiliation);

        $affiliationRepository->shouldReceive('delete')
            ->once()
            ->with($affiliation);

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $useCase = $this->app->make(RejectAffiliationInterface::class);

        $useCase->process($input);
    }

    /**
     * 異常系: アフィリエーションが存在しない場合、例外がスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testThrowsAffiliationNotFoundException(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $rejectorAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $input = new RejectAffiliationInput(
            $affiliationIdentifier,
            $rejectorAccountIdentifier,
        );

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->once()
            ->andReturnNull();

        $affiliationRepository->shouldNotReceive('delete');

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $useCase = $this->app->make(RejectAffiliationInterface::class);

        $this->expectException(AffiliationNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * 異常系: PENDINGではないアフィリエーションを拒否しようとした場合、例外がスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenNotPending(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        // ACTIVEなアフィリエーション
        $affiliation = new AccountAffiliation(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $agencyAccountIdentifier,
            AffiliationStatus::ACTIVE,
            null,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
        );

        $input = new RejectAffiliationInput(
            $affiliationIdentifier,
            $talentAccountIdentifier,
        );

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->once()
            ->andReturn($affiliation);

        $affiliationRepository->shouldNotReceive('delete');

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $useCase = $this->app->make(RejectAffiliationInterface::class);

        $this->expectException(DisallowedAffiliationOperationException::class);
        $this->expectExceptionMessage('Only pending affiliations can be rejected.');

        $useCase->process($input);
    }

    /**
     * 異常系: 承認者ではない人が拒否しようとした場合、例外がスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenNotApprover(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $otherAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        // 事務所からリクエストされた場合、タレントが承認者
        $affiliation = new AccountAffiliation(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $agencyAccountIdentifier, // requestedBy (事務所)
            AffiliationStatus::PENDING,
            null,
            new DateTimeImmutable(),
            null,
            null,
        );

        $input = new RejectAffiliationInput(
            $affiliationIdentifier,
            $otherAccountIdentifier, // 無関係な人が拒否しようとしている
        );

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->once()
            ->andReturn($affiliation);

        $affiliationRepository->shouldNotReceive('delete');

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $useCase = $this->app->make(RejectAffiliationInterface::class);

        $this->expectException(DisallowedAffiliationOperationException::class);
        $this->expectExceptionMessage('Only the designated approver can reject this affiliation.');

        $useCase->process($input);
    }
}
