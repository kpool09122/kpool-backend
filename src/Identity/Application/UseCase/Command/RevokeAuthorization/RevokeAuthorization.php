<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RevokeAuthorization;

use Source\Identity\Application\Exception\AuthorizationConnectionNotFoundException;
use Source\Identity\Application\Service\AuthorizationRevocationServiceInterface;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;

readonly class RevokeAuthorization implements RevokeAuthorizationInterface
{
    public function __construct(
        private AuthorizationConnectionRepositoryInterface $authorizationConnectionRepository,
        private AuthorizationRevocationServiceInterface $authorizationRevocationService,
    ) {
    }

    public function process(RevokeAuthorizationInputPort $input, RevokeAuthorizationOutputPort $output): void
    {
        $connection = $this->authorizationConnectionRepository->find($input->connectionIdentifier());
        if ($connection === null || (string) $connection->identityIdentifier() !== (string) $input->identityIdentifier()) {
            throw new AuthorizationConnectionNotFoundException();
        }
        $this->authorizationRevocationService->revoke($connection);
    }
}
