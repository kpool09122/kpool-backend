<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\ApproveAuthorization;

use DateTimeImmutable;
use Source\Identity\Application\Exception\InvalidAuthorizationRequestException;
use Source\Identity\Application\Service\AuthorizationAccountServiceInterface;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationConnectionServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestStorageServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestValidatorInterface;
use Source\Identity\Domain\Factory\AuthorizationConnectionFactoryInterface;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;

readonly class ApproveAuthorization implements ApproveAuthorizationInterface
{
    public function __construct(
        private AuthorizationRequestStorageServiceInterface $authorizationRequestStorageService,
        private AuthorizationRequestValidatorInterface $authorizationRequestValidator,
        private AuthorizationAccountServiceInterface $authorizationAccountService,
        private AuthorizationConnectionFactoryInterface $authorizationConnectionFactory,
        private AuthorizationConnectionRepositoryInterface $authorizationConnectionRepository,
        private AuthorizationConnectionServiceInterface $authorizationConnectionService,
    ) {
    }

    public function process(ApproveAuthorizationInputPort $input, ApproveAuthorizationOutputPort $output): void
    {
        $pending = $this->authorizationRequestStorageService->requireValid($input->requestIdentifier(), $input->identityIdentifier());
        $parameters = $pending->parameters;
        $this->authorizationRequestValidator->validate($parameters);
        $originalAccount = $this->authorizationAccountService->resolveOriginalAccount($input->identityIdentifier());
        $scopes = $input->scopes();
        if ($scopes === [] || count(array_unique(array_map(static fn (AuthorizationScope $scope): string => $scope->value, $scopes))) !== count($scopes)) {
            throw new InvalidAuthorizationRequestException();
        }
        foreach ($scopes as $scope) {
            if (! in_array($scope, $parameters->requestedScopes, true)) {
                throw new InvalidAuthorizationRequestException();
            }
        }
        $connection = $this->authorizationConnectionFactory->create(
            $input->identityIdentifier(),
            $parameters->clientIdentifier,
            $scopes,
            $originalAccount->accountIdentifier,
            $originalAccount->principalIdentifier,
            $parameters->resource,
            new DateTimeImmutable(),
        );
        $this->authorizationConnectionRepository->save($connection);
        $output->setRedirectUri($this->authorizationConnectionService->issueCode($connection->identifier(), new AuthorizationCodeParameters(
            $parameters->clientIdentifier,
            $parameters->redirectUri,
            $scopes,
            $parameters->resource,
            $parameters->codeChallenge,
            $parameters->state,
        )));
        $this->authorizationRequestStorageService->complete($pending->identifier, $input->identityIdentifier());
    }
}
