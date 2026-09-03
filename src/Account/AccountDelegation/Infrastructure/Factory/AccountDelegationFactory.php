<?php

declare(strict_types=1);

namespace Source\Account\AccountDelegation\Infrastructure\Factory;

use DateTimeImmutable;
use DomainException;
use Ramsey\Uuid\Uuid;
use Source\Account\AccountDelegation\Domain\Entity\AccountDelegation;
use Source\Account\AccountDelegation\Domain\Factory\AccountDelegationFactoryInterface;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

readonly class AccountDelegationFactory implements AccountDelegationFactoryInterface
{
    public function create(Affiliation $affiliation, AccountIdentifier $requestedByAccountIdentifier): AccountDelegation
    {
        $requestedBy = (string) $requestedByAccountIdentifier;
        $agency = (string) $affiliation->agencyAccountIdentifier();
        $talent = (string) $affiliation->talentAccountIdentifier();

        if ($requestedBy !== $agency && $requestedBy !== $talent) {
            throw new DomainException('The requesting account is not part of the affiliation.');
        }

        return new AccountDelegation(
            new DelegationIdentifier(Uuid::uuid7()->toString()),
            $affiliation->affiliationIdentifier(),
            $affiliation->agencyAccountIdentifier(),
            $affiliation->talentAccountIdentifier(),
            $requestedByAccountIdentifier,
            DelegationStatus::PENDING,
            $requestedBy === $agency ? DelegationDirection::FROM_AGENCY : DelegationDirection::FROM_TALENT,
            new DateTimeImmutable(),
            null,
            null,
        );
    }
}
