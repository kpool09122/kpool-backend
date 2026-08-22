<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Application\UseCase\Command\RejectAffiliation;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Affiliation\Application\Exception\AffiliationNotFoundException;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Application\UseCase\Command\RejectAffiliation\RejectAffiliation;
use Source\Account\Affiliation\Application\UseCase\Command\RejectAffiliation\RejectAffiliationInput;
use Source\Account\Affiliation\Application\UseCase\Command\RejectAffiliation\RejectAffiliationInterface;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectAffiliationTest extends TestCase
{
    /** @throws BindingResolutionException */
    public function test__construct(): void
    {
        $this->app->instance(AccountRepositoryInterface::class, Mockery::mock(AccountRepositoryInterface::class));
        $this->app->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));
        $this->app->instance(AffiliationRepositoryInterface::class, Mockery::mock(AffiliationRepositoryInterface::class));

        $this->assertInstanceOf(RejectAffiliation::class, $this->app->make(RejectAffiliationInterface::class));
    }

    public function testProcessWhenPolicyAllowsDesignatedApprover(): void
    {
        $data = $this->data(AccountCategory::AGENCY, AccountCategory::TALENT);
        $useCase = $this->useCase($data);

        $useCase->process(new RejectAffiliationInput($data->affiliationIdentifier, $data->principal));
    }

    public function testThrowsAffiliationNotFoundException(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $principal = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $repository = Mockery::mock(AffiliationRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->andReturnNull();
        $repository->shouldNotReceive('delete');

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        /** @var PolicyEvaluatorInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        /** @var AffiliationRepositoryInterface $repository */

        $useCase = new RejectAffiliation(
            $accountRepository,
            $policyEvaluator,
            $repository,
        );

        $this->expectException(AffiliationNotFoundException::class);
        $useCase->process(new RejectAffiliationInput($affiliationIdentifier, $principal));
    }

    public function testThrowsExceptionWhenNotPending(): void
    {
        $data = $this->data(AccountCategory::AGENCY, AccountCategory::TALENT, status: AffiliationStatus::ACTIVE);
        $useCase = $this->useCase($data, expectAccountLookup: false, expectPolicyEvaluation: false, expectDelete: false);

        $this->expectException(DisallowedAffiliationOperationException::class);
        $this->expectExceptionMessage('Only pending affiliations can be rejected.');
        $useCase->process(new RejectAffiliationInput($data->affiliationIdentifier, $data->principal));
    }

    public function testThrowsExceptionWhenNotApprover(): void
    {
        $data = $this->data(AccountCategory::AGENCY, AccountCategory::TALENT, principalAccountIdentifier: new AccountIdentifier(StrTestHelper::generateUuid()));
        $useCase = $this->useCase($data, expectAccountLookup: false, expectPolicyEvaluation: false, expectDelete: false);

        $this->expectException(DisallowedAffiliationOperationException::class);
        $this->expectExceptionMessage('Only the designated approver can reject this affiliation.');
        $useCase->process(new RejectAffiliationInput($data->affiliationIdentifier, $data->principal));
    }

    public function testThrowsWhenApproverPolicyDenies(): void
    {
        $data = $this->data(AccountCategory::AGENCY, AccountCategory::TALENT, policyAllowed: false);
        $useCase = $this->useCase($data, expectDelete: false);

        $this->expectException(DisallowedAffiliationOperationException::class);
        $this->expectExceptionMessage('Affiliation rejection is not allowed.');
        $useCase->process(new RejectAffiliationInput($data->affiliationIdentifier, $data->principal));
    }

    public function testThrowsWhenApproverAccountCategoryIsGeneral(): void
    {
        $data = $this->data(AccountCategory::AGENCY, AccountCategory::GENERAL, policyAllowed: false);
        $useCase = $this->useCase($data, expectDelete: false);

        $this->expectException(DisallowedAffiliationOperationException::class);
        $useCase->process(new RejectAffiliationInput($data->affiliationIdentifier, $data->principal));
    }

    private function useCase(RejectAffiliationTestData $data, bool $expectAccountLookup = true, bool $expectPolicyEvaluation = true, bool $expectDelete = true): RejectAffiliation
    {
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        if ($expectAccountLookup) {
            $accountRepository->shouldReceive('findById')->with($data->approverAccountIdentifier)->andReturn($data->approverAccount);
        } else {
            $accountRepository->shouldNotReceive('findById');
        }

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        if ($expectPolicyEvaluation) {
            $policyEvaluator->shouldReceive('evaluate')
                ->once()
                ->with(
                    $data->principal,
                    Action::AFFILIATION_REJECT,
                    Mockery::on(fn (Resource $resource): bool => $resource->accountIdentifier() === $data->approverAccountIdentifier
                        && $resource->accountCategory() === $data->approverAccountCategory
                        && $resource->affiliationRequestingAccountCategory() === $data->requestingAccountCategory)
                )
                ->andReturn($data->policyAllowed);
        } else {
            $policyEvaluator->shouldNotReceive('evaluate');
        }

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')->with($data->affiliationIdentifier)->once()->andReturn($data->affiliation);
        if ($expectDelete) {
            $affiliationRepository->shouldReceive('delete')->once()->with($data->affiliation);
        } else {
            $affiliationRepository->shouldNotReceive('delete');
        }

        /** @var AccountRepositoryInterface $accountRepository */
        /** @var PolicyEvaluatorInterface $policyEvaluator */
        /** @var AffiliationRepositoryInterface $affiliationRepository */
        return new RejectAffiliation($accountRepository, $policyEvaluator, $affiliationRepository);
    }

    private function data(AccountCategory $requestingCategory, AccountCategory $approverCategory, bool $policyAllowed = true, ?AccountIdentifier $principalAccountIdentifier = null, AffiliationStatus $status = AffiliationStatus::PENDING): RejectAffiliationTestData
    {
        $agency = new AccountIdentifier(StrTestHelper::generateUuid());
        $talent = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestedBy = $requestingCategory === AccountCategory::AGENCY ? $agency : $talent;
        $approver = $requestingCategory === AccountCategory::AGENCY ? $talent : $agency;
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $affiliation = new Affiliation($affiliationIdentifier, $agency, $talent, $requestedBy, $status, null, new DateTimeImmutable(), $status === AffiliationStatus::ACTIVE ? new DateTimeImmutable() : null, null);
        $principal = $this->principal($principalAccountIdentifier ?? $approver);

        return new RejectAffiliationTestData(
            $affiliationIdentifier,
            $approver,
            $principal,
            $affiliation,
            $this->account($approver, $approverCategory),
            $requestingCategory,
            $approverCategory,
            $policyAllowed,
        );
    }

    private function principal(AccountIdentifier $accountIdentifier): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $accountIdentifier);
    }

    private function account(AccountIdentifier $identifier, AccountCategory $category): Account
    {
        return new Account($identifier, new Email('account@example.com'), AccountType::CORPORATION, new AccountName('Test Account'), AccountStatus::ACTIVE, $category, DeletionReadinessChecklist::ready(), new AccountDocuments());
    }
}

readonly class RejectAffiliationTestData
{
    public function __construct(
        public AffiliationIdentifier $affiliationIdentifier,
        public AccountIdentifier $approverAccountIdentifier,
        public Principal $principal,
        public Affiliation $affiliation,
        public Account $approverAccount,
        public AccountCategory $requestingAccountCategory,
        public AccountCategory $approverAccountCategory,
        public bool $policyAllowed,
    ) {
    }
}
