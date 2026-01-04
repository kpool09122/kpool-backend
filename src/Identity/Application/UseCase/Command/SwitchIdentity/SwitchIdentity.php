<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SwitchIdentity;

use DomainException;
use Source\Identity\Application\Service\DelegationValidatorInterface;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;

readonly class SwitchIdentity implements SwitchIdentityInterface
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private DelegationValidatorInterface $delegationValidator,
        private AuthServiceInterface $authService,
    ) {
    }

    /**
     * @param SwitchIdentityInputPort $input
     * @return Identity
     * @throws IdentityNotFoundException
     */
    public function process(SwitchIdentityInputPort $input): Identity
    {
        $currentIdentity = $this->identityRepository->findById($input->currentIdentityIdentifier());

        if ($currentIdentity === null) {
            throw new IdentityNotFoundException('Current identity not found.');
        }

        // Determine the original identity
        $originalIdentity = $currentIdentity->isDelegatedIdentity()
            ? $this->identityRepository->findById($currentIdentity->originalIdentityIdentifier())
            : $currentIdentity;

        if ($originalIdentity === null) {
            throw new IdentityNotFoundException('Original identity not found.');
        }

        // If targetDelegationIdentifier is null, switch back to original identity
        $targetDelegationIdentifier = $input->targetDelegationIdentifier();
        if ($targetDelegationIdentifier === null) {
            if (! $currentIdentity->isDelegatedIdentity()) {
                throw new DomainException('Already using original identity.');
            }

            $this->authService->logout();
            $this->authService->login($originalIdentity);

            return $originalIdentity;
        }

        // Validate delegation
        if (! $this->delegationValidator->isValid($targetDelegationIdentifier)) {
            throw new DomainException('Delegation is not valid.');
        }

        // Find the delegated identity
        $delegatedIdentity = $this->identityRepository->findByDelegation($targetDelegationIdentifier);

        if ($delegatedIdentity === null) {
            throw new IdentityNotFoundException('Delegated identity not found.');
        }

        // Verify the delegated identity belongs to the original identity
        if ((string) $delegatedIdentity->originalIdentityIdentifier() !== (string) $originalIdentity->identityIdentifier()) {
            throw new DomainException('Delegation does not belong to the current identity.');
        }

        $this->authService->logout();
        $this->authService->login($delegatedIdentity);

        return $delegatedIdentity;
    }
}
