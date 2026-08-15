<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation;

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
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliation;
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliationInput;
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliationInterface;
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliationOutput;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Event\AffiliationActivated;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveAffiliationTest extends TestCase
{
    /** @throws BindingResolutionException */
    public function test__construct(): void
    {
        $this->app->instance(AccountRepositoryInterface::class, Mockery::mock(AccountRepositoryInterface::class));
        $this->app->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));
        $this->app->instance(AffiliationRepositoryInterface::class, Mockery::mock(AffiliationRepositoryInterface::class));
        $this->app->instance(EventDispatcherInterface::class, Mockery::mock(EventDispatcherInterface::class));

        $this->assertInstanceOf(ApproveAffiliation::class, $this->app->make(ApproveAffiliationInterface::class));
    }

    public function testProcessWhenPolicyAllowsDesignatedApprover(): void
    {
        $data = $this->data(AccountCategory::AGENCY, AccountCategory::TALENT, policyAllowed: true);
        $useCase = $this->useCase($data);
        $output = new ApproveAffiliationOutput();

        $useCase->process(new ApproveAffiliationInput($data->affiliationIdentifier, $data->principal), $output);

        $this->assertSame(AffiliationStatus::ACTIVE->value, $output->toArray()['status']);
        $this->assertNotNull($output->toArray()['activatedAt']);
    }

    public function testThrowsWhenAffiliationNotFound(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $principal = $this->principal(new AccountIdentifier(StrTestHelper::generateUuid()));
        $repository = Mockery::mock(AffiliationRepositoryInterface::class);
        $repository->shouldReceive('findById')->with($affiliationIdentifier)->once()->andReturnNull();

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        /** @var PolicyEvaluatorInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        /** @var AffiliationRepositoryInterface $repository */
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $useCase = new ApproveAffiliation(
            $accountRepository,
            $policyEvaluator,
            $repository,
            $eventDispatcher,
        );

        $this->expectException(AffiliationNotFoundException::class);
        $useCase->process(new ApproveAffiliationInput($affiliationIdentifier, $principal), new ApproveAffiliationOutput());
    }

    public function testThrowsWhenPrincipalIsNotDesignatedApprover(): void
    {
        $data = $this->data(AccountCategory::AGENCY, AccountCategory::TALENT, principalAccountIdentifier: new AccountIdentifier(StrTestHelper::generateUuid()));
        $useCase = $this->useCase($data, expectAccountLookup: false, expectPolicyEvaluation: false, expectSave: false);

        $this->expectException(DisallowedAffiliationOperationException::class);
        $this->expectExceptionMessage('Only the designated approver can approve this affiliation.');
        $useCase->process(new ApproveAffiliationInput($data->affiliationIdentifier, $data->principal), new ApproveAffiliationOutput());
    }

    public function testThrowsWhenApproverPolicyDenies(): void
    {
        $data = $this->data(AccountCategory::AGENCY, AccountCategory::TALENT, policyAllowed: false);
        $useCase = $this->useCase($data, expectSave: false);

        $this->expectException(DisallowedAffiliationOperationException::class);
        $this->expectExceptionMessage('Affiliation approval is not allowed.');
        $useCase->process(new ApproveAffiliationInput($data->affiliationIdentifier, $data->principal), new ApproveAffiliationOutput());
    }

    public function testThrowsWhenTalentAlreadyHasAnotherActiveAffiliationOnApproval(): void
    {
        $data = $this->data(AccountCategory::AGENCY, AccountCategory::TALENT, activeTalentExists: true);
        $useCase = $this->useCase($data, expectSave: false);

        $this->expectException(DisallowedAffiliationOperationException::class);
        $this->expectExceptionMessage('The talent account already has an active affiliation.');
        $useCase->process(new ApproveAffiliationInput($data->affiliationIdentifier, $data->principal), new ApproveAffiliationOutput());
    }

    public function testThrowsWhenApproverAccountCategoryIsGeneral(): void
    {
        $data = $this->data(AccountCategory::AGENCY, AccountCategory::GENERAL, policyAllowed: false);
        $useCase = $this->useCase($data, expectSave: false);

        $this->expectException(DisallowedAffiliationOperationException::class);
        $useCase->process(new ApproveAffiliationInput($data->affiliationIdentifier, $data->principal), new ApproveAffiliationOutput());
    }

    private function useCase(ApproveAffiliationTestData $data, bool $expectAccountLookup = true, bool $expectPolicyEvaluation = true, bool $expectSave = true): ApproveAffiliation
    {
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        if ($expectAccountLookup) {
            $accountRepository->shouldReceive('findById')->with($data->agencyAccountIdentifier)->andReturn($data->agencyAccount);
            $accountRepository->shouldReceive('findById')->with($data->talentAccountIdentifier)->andReturn($data->talentAccount);
        } else {
            $accountRepository->shouldNotReceive('findById');
        }

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $approverAccount = $data->approverAccountIdentifier === $data->agencyAccountIdentifier ? $data->agencyAccount : $data->talentAccount;
        if ($expectPolicyEvaluation) {
            $policyEvaluator->shouldReceive('evaluate')
                ->once()
                ->with(
                    $data->principal,
                    Action::AFFILIATION_APPROVE,
                    Mockery::on(fn (Resource $resource): bool => $resource->accountIdentifier() === $data->approverAccountIdentifier
                        && $resource->accountCategory() === $approverAccount->accountCategory()
                        && $resource->affiliationRequestingAccountCategory() === $data->requestingAccountCategory)
                )
                ->andReturn($data->policyAllowed);
        } else {
            $policyEvaluator->shouldNotReceive('evaluate');
        }

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')->with($data->affiliationIdentifier)->once()->andReturn($data->affiliation);
        if ($expectPolicyEvaluation && $data->policyAllowed) {
            $affiliationRepository->shouldReceive('findActiveByTalentAccount')
                ->with($data->talentAccountIdentifier)
                ->once()
                ->andReturn($data->activeTalentAffiliation);
        }
        if ($expectSave) {
            $affiliationRepository->shouldReceive('save')->once()->with($data->affiliation);
        } else {
            $affiliationRepository->shouldNotReceive('save');
        }

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        if ($expectSave) {
            $eventDispatcher->shouldReceive('dispatch')->once()->with(Mockery::on(static fn (AffiliationActivated $event): bool => $event->agencyAccountIdentifier() === $data->agencyAccountIdentifier
                && $event->talentAccountIdentifier() === $data->talentAccountIdentifier
                && $event->agencyAccountType() === $data->agencyAccount->type()
                && $event->talentAccountType() === $data->talentAccount->type()
                && $event->agencyAccountName() === (string) $data->agencyAccount->name()
                && $event->talentAccountName() === (string) $data->talentAccount->name()));
        } else {
            $eventDispatcher->shouldNotReceive('dispatch');
        }

        /** @var AccountRepositoryInterface $accountRepository */
        /** @var PolicyEvaluatorInterface $policyEvaluator */
        /** @var AffiliationRepositoryInterface $affiliationRepository */
        /** @var EventDispatcherInterface $eventDispatcher */
        return new ApproveAffiliation($accountRepository, $policyEvaluator, $affiliationRepository, $eventDispatcher);
    }

    private function data(AccountCategory $requestingCategory, AccountCategory $approverCategory, bool $policyAllowed = true, ?AccountIdentifier $principalAccountIdentifier = null, bool $activeTalentExists = false): ApproveAffiliationTestData
    {
        $agency = new AccountIdentifier(StrTestHelper::generateUuid());
        $talent = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestedBy = $requestingCategory === AccountCategory::AGENCY ? $agency : $talent;
        $approver = $requestingCategory === AccountCategory::AGENCY ? $talent : $agency;
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $affiliation = new Affiliation($affiliationIdentifier, $agency, $talent, $requestedBy, AffiliationStatus::PENDING, null, new DateTimeImmutable(), null, null);
        $activeTalentAffiliation = $activeTalentExists
            ? new Affiliation(new AffiliationIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()), $talent, new AccountIdentifier(StrTestHelper::generateUuid()), AffiliationStatus::ACTIVE, null, new DateTimeImmutable(), new DateTimeImmutable(), null)
            : null;
        $principal = $this->principal($principalAccountIdentifier ?? $approver);
        $agencyCategory = $approver === $agency ? $approverCategory : AccountCategory::AGENCY;
        $talentCategory = $approver === $talent ? $approverCategory : AccountCategory::TALENT;
        $agencyAccount = $this->account($agency, $agencyCategory, 'Agency Alpha');
        $talentAccount = $this->account($talent, $talentCategory, 'Talent Beta');

        return new ApproveAffiliationTestData(
            $affiliationIdentifier,
            $agency,
            $talent,
            $approver,
            $principal,
            $affiliation,
            $activeTalentAffiliation,
            $agencyAccount,
            $talentAccount,
            $requestingCategory,
            $approverCategory,
            $policyAllowed,
        );
    }

    private function principal(AccountIdentifier $accountIdentifier): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $accountIdentifier);
    }

    private function account(AccountIdentifier $identifier, AccountCategory $category, string $name): Account
    {
        return new Account($identifier, new Email('account@example.com'), AccountType::CORPORATION, new AccountName($name), AccountStatus::ACTIVE, $category, DeletionReadinessChecklist::ready(), new AccountDocuments());
    }
}

readonly class ApproveAffiliationTestData
{
    public function __construct(
        public AffiliationIdentifier $affiliationIdentifier,
        public AccountIdentifier $agencyAccountIdentifier,
        public AccountIdentifier $talentAccountIdentifier,
        public AccountIdentifier $approverAccountIdentifier,
        public Principal $principal,
        public Affiliation $affiliation,
        public ?Affiliation $activeTalentAffiliation,
        public Account $agencyAccount,
        public Account $talentAccount,
        public AccountCategory $requestingAccountCategory,
        public AccountCategory $approverAccountCategory,
        public bool $policyAllowed,
    ) {
    }
}
