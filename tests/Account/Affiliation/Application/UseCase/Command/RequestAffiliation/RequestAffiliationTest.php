<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Application\UseCase\Command\RequestAffiliation;

use DateTimeImmutable;
use Mockery;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Affiliation\Application\Exception\AffiliationAlreadyExistsException;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliation;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationInput;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationOutput;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Event\AffiliationRequested;
use Source\Account\Affiliation\Domain\Factory\AffiliationFactoryInterface;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestAffiliationTest extends TestCase
{
    public function testProcessFromTalentToAgency(): void
    {
        $data = $this->createTestData(AccountCategory::TALENT);
        $useCase = $this->createUseCase($data);

        $output = new RequestAffiliationOutput();
        $useCase->process($data->input, $output);

        $this->assertSame((string) $data->affiliationIdentifier, $output->toArray()['affiliationIdentifier']);
        $this->assertSame((string) $data->agencyAccountIdentifier, $output->toArray()['agencyAccountIdentifier']);
        $this->assertSame((string) $data->talentAccountIdentifier, $output->toArray()['talentAccountIdentifier']);
    }

    public function testProcessFromAgencyToTalent(): void
    {
        $data = $this->createTestData(AccountCategory::AGENCY);
        $useCase = $this->createUseCase($data);

        $output = new RequestAffiliationOutput();
        $useCase->process($data->input, $output);

        $this->assertSame((string) $data->affiliationIdentifier, $output->toArray()['affiliationIdentifier']);
    }

    public function testThrowsWhenRequestingPrincipalLacksPolicy(): void
    {
        $data = $this->createTestData(AccountCategory::TALENT, requesterAllowed: false);
        $useCase = $this->createUseCase($data);

        $this->expectException(DisallowedAffiliationOperationException::class);
        $useCase->process($data->input, new RequestAffiliationOutput());
    }

    public function testThrowsWhenRequestingAccountIsGeneral(): void
    {
        $data = $this->createTestData(AccountCategory::GENERAL);
        $useCase = $this->createUseCase($data);

        $this->expectException(DisallowedAffiliationOperationException::class);
        $useCase->process($data->input, new RequestAffiliationOutput());
    }

    public function testTargetResolutionFailuresUseSameBusinessException(): void
    {
        foreach (['missing', 'category', 'policy'] as $failure) {
            $data = $this->createTestData(AccountCategory::TALENT, targetFailure: $failure);
            $useCase = $this->createUseCase($data);

            try {
                $useCase->process($data->input, new RequestAffiliationOutput());
                $this->fail('Expected exception was not thrown.');
            } catch (DisallowedAffiliationOperationException $e) {
                $this->assertSame('Affiliation request target is not allowed.', $e->getMessage());
            }
        }
    }

    public function testThrowsWhenSameAgencyTalentActiveAffiliationAlreadyExists(): void
    {
        $data = $this->createTestData(AccountCategory::TALENT, activeExists: true);
        $useCase = $this->createUseCase($data);

        $this->expectException(AffiliationAlreadyExistsException::class);
        $this->expectExceptionMessage('An active affiliation already exists between these accounts.');
        $useCase->process($data->input, new RequestAffiliationOutput());
    }

    public function testThrowsWhenTalentAlreadyHasActiveAffiliationWithAnotherAgency(): void
    {
        $data = $this->createTestData(AccountCategory::AGENCY, activeTalentExists: true);
        $useCase = $this->createUseCase($data);

        $this->expectException(AffiliationAlreadyExistsException::class);
        $this->expectExceptionMessage('The talent account already has an active affiliation.');
        $useCase->process($data->input, new RequestAffiliationOutput());
    }

    public function testAgencyCanRequestAnotherTalentWhenTargetTalentHasNoActiveAffiliation(): void
    {
        $data = $this->createTestData(AccountCategory::AGENCY);
        $useCase = $this->createUseCase($data);

        $output = new RequestAffiliationOutput();
        $useCase->process($data->input, $output);

        $this->assertSame((string) $data->affiliationIdentifier, $output->toArray()['affiliationIdentifier']);
    }

    public function testTerminatedAffiliationDoesNotBlockRequest(): void
    {
        $data = $this->createTestData(AccountCategory::TALENT);
        $useCase = $this->createUseCase($data);

        $output = new RequestAffiliationOutput();
        $useCase->process($data->input, $output);

        $this->assertSame((string) $data->affiliationIdentifier, $output->toArray()['affiliationIdentifier']);
    }

    private function createUseCase(RequestAffiliationTestData $data): RequestAffiliation
    {
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->with($data->requestingAccountIdentifier)->andReturn($data->requestingAccount);
        $accountRepository->shouldReceive('findByEmail')->with($data->targetEmail)->andReturn($data->targetAccount);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        if ($data->expectTargetPrincipalLookup) {
            $principalRepository->shouldReceive('findByEmailAndAccountIdentifier')
                ->with($data->targetEmail, $data->targetAccountIdentifier)
                ->andReturn($data->targetPrincipal);
        }

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->with(
                $data->requestingPrincipal,
                Action::AFFILIATION_REQUEST_CREATE,
                Mockery::on(static fn (Resource $resource): bool => $resource->accountIdentifier() === $data->requestingAccountIdentifier
                    && $resource->accountCategory() === $data->requestingAccount->accountCategory()
                    && $resource->affiliationRequestingAccountCategory() === null)
            )
            ->andReturn($data->requesterAllowed);
        if ($data->expectTargetPolicyEvaluation) {
            $policyEvaluator->shouldReceive('evaluate')
                ->with(
                    $data->targetPrincipal,
                    Action::AFFILIATION_REQUEST_RECEIVE,
                    Mockery::on(static fn (Resource $resource): bool => $data->targetAccount !== null
                        && $resource->accountIdentifier() === $data->targetAccountIdentifier
                        && $resource->accountCategory() === $data->targetAccount->accountCategory()
                        && $resource->affiliationRequestingAccountCategory() === $data->requestingAccount->accountCategory())
                )
                ->andReturn($data->targetAllowed);
        }

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        if ($data->expectAffiliationLookup) {
            $affiliationRepository->shouldReceive('existsActiveAffiliation')
                ->with($data->agencyAccountIdentifier, $data->talentAccountIdentifier)
                ->andReturn($data->activeExists);
            if (! $data->activeExists) {
                $affiliationRepository->shouldReceive('findActiveByTalentAccount')
                    ->with($data->talentAccountIdentifier)
                    ->andReturn($data->activeTalentAffiliation);
            }
        }
        if ($data->expectSave) {
            $affiliationRepository->shouldReceive('save')->once()->with($data->affiliation);
        }

        $affiliationFactory = Mockery::mock(AffiliationFactoryInterface::class);
        if ($data->expectSave) {
            $affiliationFactory->shouldReceive('create')
                ->with($data->agencyAccountIdentifier, $data->talentAccountIdentifier, $data->requestingAccountIdentifier, $data->terms)
                ->andReturn($data->affiliation);
        }

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        if ($data->expectSave) {
            $eventDispatcher->shouldReceive('dispatch')
                ->once()
                ->with(Mockery::on(static fn (AffiliationRequested $event): bool => $event->affiliationIdentifier === $data->affiliationIdentifier
                    && $event->targetEmail === $data->targetEmail
                    && $event->agencyAccountIdentifier === $data->agencyAccountIdentifier
                    && $event->talentAccountIdentifier === $data->talentAccountIdentifier
                    && $event->requestedBy === $data->requestingAccountIdentifier));
        } else {
            $eventDispatcher->shouldNotReceive('dispatch');
        }

        /** @var AccountRepositoryInterface $accountRepository */
        /** @var PrincipalRepositoryInterface $principalRepository */
        /** @var PolicyEvaluatorInterface $policyEvaluator */
        /** @var AffiliationRepositoryInterface $affiliationRepository */
        /** @var AffiliationFactoryInterface $affiliationFactory */
        /** @var EventDispatcherInterface $eventDispatcher */
        return new RequestAffiliation(
            $accountRepository,
            $principalRepository,
            $policyEvaluator,
            $affiliationRepository,
            $affiliationFactory,
            $eventDispatcher,
        );
    }

    private function createTestData(AccountCategory $requestingCategory, bool $requesterAllowed = true, ?string $targetFailure = null, bool $activeExists = false, bool $activeTalentExists = false): RequestAffiliationTestData
    {
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestingAccountIdentifier = $requestingCategory === AccountCategory::AGENCY ? $agencyAccountIdentifier : $talentAccountIdentifier;
        $targetAccountIdentifier = $requestingCategory === AccountCategory::AGENCY ? $talentAccountIdentifier : $agencyAccountIdentifier;
        $targetEmail = new Email('target@example.com');
        $requestingPrincipal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $requestingAccountIdentifier);
        $targetPrincipal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $targetAccountIdentifier);
        $requestingAccount = $this->account($requestingAccountIdentifier, $requestingCategory, 'requester@example.com');
        $expectedTargetCategory = $requestingCategory === AccountCategory::AGENCY ? AccountCategory::TALENT : AccountCategory::AGENCY;
        $targetAccount = $targetFailure === 'missing' ? null : $this->account($targetAccountIdentifier, $targetFailure === 'category' ? AccountCategory::GENERAL : $expectedTargetCategory, (string) $targetEmail);
        $terms = new AffiliationTerms(new Percentage(30), 'Contract notes');
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $affiliation = new Affiliation($affiliationIdentifier, $agencyAccountIdentifier, $talentAccountIdentifier, $requestingAccountIdentifier, AffiliationStatus::PENDING, $terms, new DateTimeImmutable(), null, null);
        $activeTalentAffiliation = $activeTalentExists
            ? new Affiliation(new AffiliationIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()), $talentAccountIdentifier, new AccountIdentifier(StrTestHelper::generateUuid()), AffiliationStatus::ACTIVE, null, new DateTimeImmutable(), new DateTimeImmutable(), null)
            : null;
        $requesterAllowed = $requesterAllowed && $requestingCategory !== AccountCategory::GENERAL;
        $targetAllowed = $targetFailure !== 'policy' && $targetAccount !== null && $this->isAllowedAffiliationRequestPair($requestingCategory, $targetAccount->accountCategory());

        return new RequestAffiliationTestData(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $requestingAccountIdentifier,
            $targetAccountIdentifier,
            $requestingPrincipal,
            $targetPrincipal,
            $targetEmail,
            $terms,
            $requestingAccount,
            $targetAccount,
            $affiliation,
            new RequestAffiliationInput($requestingPrincipal, $targetEmail, $terms),
            $requesterAllowed,
            $targetAllowed,
            $activeExists,
            $activeTalentAffiliation,
            $requesterAllowed && $targetAccount !== null,
            $requesterAllowed && $targetAccount !== null,
            $requesterAllowed && $targetAccount !== null && $targetAllowed,
            $requesterAllowed && $targetAccount !== null && $targetAllowed && ! $activeExists && $activeTalentAffiliation === null,
        );
    }

    private function isAllowedAffiliationRequestPair(AccountCategory $requestingCategory, AccountCategory $targetCategory): bool
    {
        return match ($requestingCategory) {
            AccountCategory::TALENT => $targetCategory === AccountCategory::AGENCY,
            AccountCategory::AGENCY => $targetCategory === AccountCategory::TALENT,
            default => false,
        };
    }

    private function account(AccountIdentifier $identifier, AccountCategory $category, string $email): Account
    {
        return new Account($identifier, new Email($email), AccountType::CORPORATION, new AccountName('Test Account'), AccountStatus::ACTIVE, $category, DeletionReadinessChecklist::ready(), new AccountDocuments());
    }
}

readonly class RequestAffiliationTestData
{
    public function __construct(
        public AffiliationIdentifier $affiliationIdentifier,
        public AccountIdentifier $agencyAccountIdentifier,
        public AccountIdentifier $talentAccountIdentifier,
        public AccountIdentifier $requestingAccountIdentifier,
        public AccountIdentifier $targetAccountIdentifier,
        public Principal $requestingPrincipal,
        public Principal $targetPrincipal,
        public Email $targetEmail,
        public ?AffiliationTerms $terms,
        public Account $requestingAccount,
        public ?Account $targetAccount,
        public Affiliation $affiliation,
        public RequestAffiliationInput $input,
        public bool $requesterAllowed,
        public bool $targetAllowed,
        public bool $activeExists,
        public ?Affiliation $activeTalentAffiliation,
        public bool $expectTargetPrincipalLookup,
        public bool $expectTargetPolicyEvaluation,
        public bool $expectAffiliationLookup,
        public bool $expectSave,
    ) {
    }
}
