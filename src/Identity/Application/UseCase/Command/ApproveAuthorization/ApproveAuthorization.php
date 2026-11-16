<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\ApproveAuthorization;

use DateTimeImmutable;
use Source\Identity\Application\Exception\InvalidAuthorizationConsentException;
use Source\Identity\Application\Service\AuthorizationAccountServiceInterface;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationConsentServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestStorageServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestValidatorInterface;
use Source\Identity\Domain\Factory\AuthorizationConsentFactoryInterface;
use Source\Identity\Domain\Repository\AuthorizationConsentRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;

readonly class ApproveAuthorization implements ApproveAuthorizationInterface
{
    public function __construct(
        private AuthorizationRequestStorageServiceInterface $authorizationRequestStorageService,
        private AuthorizationRequestValidatorInterface $authorizationRequestValidator,
        private AuthorizationAccountServiceInterface $authorizationAccountService,
        private AuthorizationConsentFactoryInterface $authorizationConsentFactory,
        private AuthorizationConsentRepositoryInterface $authorizationConsentRepository,
        private AuthorizationConsentServiceInterface $authorizationConsentService,
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
            throw new InvalidAuthorizationConsentException();
        }
        foreach ($scopes as $scope) {
            if (! in_array($scope, $parameters->requestedScopes, true)) {
                throw new InvalidAuthorizationConsentException();
            }
        }
        $consent = $this->authorizationConsentFactory->create(
            $input->identityIdentifier(),
            $parameters->clientIdentifier,
            $scopes,
            $originalAccount->accountIdentifier,
            $originalAccount->principalIdentifier,
            $parameters->resource,
            new DateTimeImmutable(),
        );
        $this->authorizationConsentRepository->save($consent);
        $output->setRedirectUri($this->authorizationConsentService->issueCode($consent->identifier(), new AuthorizationCodeParameters(
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
