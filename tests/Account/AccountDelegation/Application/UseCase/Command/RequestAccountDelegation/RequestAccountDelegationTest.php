<?php

declare(strict_types=1);

namespace Tests\Account\AccountDelegation\Application\UseCase\Command\RequestAccountDelegation;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\AccountDelegation\Application\Exception\AccountDelegationForbiddenException;
use Source\Account\AccountDelegation\Application\Exception\AccountDelegationUnavailableException;
use Source\Account\AccountDelegation\Application\UseCase\Command\RequestAccountDelegation\RequestAccountDelegation;
use Source\Account\AccountDelegation\Application\UseCase\Command\RequestAccountDelegation\RequestAccountDelegationInput;
use Source\Account\AccountDelegation\Application\UseCase\Command\RequestAccountDelegation\RequestAccountDelegationOutput;
use Source\Account\AccountDelegation\Domain\Entity\AccountDelegation;
use Source\Account\AccountDelegation\Domain\Exception\AccountDelegationAlreadyExistsException;
use Source\Account\AccountDelegation\Domain\Factory\AccountDelegationFactoryInterface;
use Source\Account\AccountDelegation\Domain\Repository\AccountDelegationRepositoryInterface;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestAccountDelegationTest extends TestCase
{
    public function testOwnerCanRequestDelegationFromActiveAffiliation(): void
    {
        [$useCase, $input, $output, $delegation] = $this->createUseCase(allowed: true, targetExists: true, affiliationExists: true, duplicate: false);
        $useCase->process($input, $output);
        $this->assertSame((string) $delegation->delegationIdentifier(), $output->toArray()['delegationIdentifier']);
        $this->assertSame('pending', $output->toArray()['status']);
    }

    public function testRejectsPrincipalWithoutPolicy(): void
    {
        [$useCase, $input, $output] = $this->createUseCase(allowed: false, targetExists: true, affiliationExists: true, duplicate: false);
        $this->expectException(AccountDelegationForbiddenException::class);
        $useCase->process($input, $output);
    }

    public function testDoesNotRevealMissingTarget(): void
    {
        [$useCase, $input, $output] = $this->createUseCase(allowed: true, targetExists: false, affiliationExists: false, duplicate: false);
        $this->expectException(AccountDelegationUnavailableException::class);
        $useCase->process($input, $output);
    }

    public function testDoesNotRevealMissingActiveAffiliation(): void
    {
        [$useCase, $input, $output] = $this->createUseCase(allowed: true, targetExists: true, affiliationExists: false, duplicate: false);
        $this->expectException(AccountDelegationUnavailableException::class);
        $useCase->process($input, $output);
    }

    public function testRejectsDuplicateOpenDelegation(): void
    {
        [$useCase, $input, $output] = $this->createUseCase(allowed: true, targetExists: true, affiliationExists: true, duplicate: true);
        $this->expectException(AccountDelegationAlreadyExistsException::class);
        $useCase->process($input, $output);
    }

    /** @return array{RequestAccountDelegation, RequestAccountDelegationInput, RequestAccountDelegationOutput, AccountDelegation} */
    private function createUseCase(bool $allowed, bool $targetExists, bool $affiliationExists, bool $duplicate): array
    {
        $agencyId = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentId = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $agencyId,
        );
        $requestingAccount = Mockery::mock(Account::class);
        $requestingAccount->shouldReceive('accountIdentifier')->andReturn($agencyId);
        $requestingAccount->shouldReceive('type')->andReturn(AccountType::CORPORATION);
        $requestingAccount->shouldReceive('accountCategory')->andReturn(AccountCategory::AGENCY);
        $targetAccount = Mockery::mock(Account::class);

        /** @var AccountRepositoryInterface&MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->with($agencyId)->once()->andReturn($requestingAccount);
        if ($allowed) {
            $accountRepository->shouldReceive('findById')->with($talentId)->once()->andReturn($targetExists ? $targetAccount : null);
        }

        /** @var PolicyEvaluatorInterface&MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->with($principal, Mockery::any(), Mockery::type(Resource::class))->once()->andReturn($allowed);

        $affiliation = new Affiliation(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $agencyId,
            $talentId,
            $agencyId,
            AffiliationStatus::ACTIVE,
            null,
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable(),
            null,
        );
        /** @var AffiliationRepositoryInterface&MockInterface $affiliationRepository */
        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        if ($allowed && $targetExists) {
            $affiliationRepository->shouldReceive('findActiveBetweenAccounts')->with($agencyId, $talentId)->once()->andReturn($affiliationExists ? $affiliation : null);
        }

        $delegation = new AccountDelegation(
            new DelegationIdentifier(StrTestHelper::generateUuid()),
            $affiliation->affiliationIdentifier(),
            $agencyId,
            $talentId,
            $agencyId,
            DelegationStatus::PENDING,
            DelegationDirection::FROM_AGENCY,
            new DateTimeImmutable(),
            null,
            null,
        );
        /** @var AccountDelegationRepositoryInterface&MockInterface $delegationRepository */
        $delegationRepository = Mockery::mock(AccountDelegationRepositoryInterface::class);
        /** @var AccountDelegationFactoryInterface&MockInterface $factory */
        $factory = Mockery::mock(AccountDelegationFactoryInterface::class);
        if ($allowed && $targetExists && $affiliationExists) {
            $delegationRepository->shouldReceive('existsOpenByAffiliation')->with($affiliation->affiliationIdentifier())->once()->andReturn($duplicate);
            if (! $duplicate) {
                $factory->shouldReceive('create')->with($affiliation, $agencyId)->once()->andReturn($delegation);
                $delegationRepository->shouldReceive('save')->with($delegation)->once();
            }
        }

        return [
            new RequestAccountDelegation($accountRepository, $affiliationRepository, $delegationRepository, $factory, $policyEvaluator),
            new RequestAccountDelegationInput($principal, $talentId),
            new RequestAccountDelegationOutput(),
            $delegation,
        ];
    }
}
