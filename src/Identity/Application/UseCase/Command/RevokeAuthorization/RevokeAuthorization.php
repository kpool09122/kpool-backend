<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RevokeAuthorization;

use Source\Identity\Application\Exception\AuthorizationConnectionNotFoundException;
use Source\Identity\Application\Service\AuthorizationRevocationServiceInterface;
use Source\Identity\Domain\Repository\AuthorizationConsentRepositoryInterface;

readonly class RevokeAuthorization implements RevokeAuthorizationInterface
{
    public function __construct(
        private AuthorizationConsentRepositoryInterface $authorizationConsentRepository,
        private AuthorizationRevocationServiceInterface $authorizationRevocationService,
    ) {
    }

    public function process(RevokeAuthorizationInputPort $input, RevokeAuthorizationOutputPort $output): void
    {
        $consent = $this->authorizationConsentRepository->find($input->consentIdentifier());
        if ($consent === null || (string) $consent->identityIdentifier() !== (string) $input->identityIdentifier()) {
            throw new AuthorizationConnectionNotFoundException();
        }
        $this->authorizationRevocationService->revoke($consent);
    }
}
