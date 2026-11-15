<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Query;

use Application\Models\Account\Delegation;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Identity\Application\Service\AuthorizationAccountServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestStorageServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestValidatorInterface;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\AuthorizationRequestReadModel;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\GetAuthorizationRequestInputPort;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\GetAuthorizationRequestInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

readonly class GetAuthorizationRequest implements GetAuthorizationRequestInterface
{
    public function __construct(
        private AuthorizationRequestStorageServiceInterface $authorizationRequestStorageService,
        private AuthorizationRequestValidatorInterface $authorizationRequestValidator,
        private AuthorizationAccountServiceInterface $authorizationAccountService,
    ) {
    }

    public function process(GetAuthorizationRequestInputPort $input): AuthorizationRequestReadModel
    {
        $identityIdentifier = $input->identityIdentifier();
        $pending = $this->authorizationRequestStorageService->requireValid($input->requestIdentifier(), $identityIdentifier);
        $clientName = $this->authorizationRequestValidator->validate($pending->parameters);
        $originalAccount = $this->authorizationAccountService->resolveOriginalAccount($identityIdentifier);
        $originalAccountIdentifier = (string) $originalAccount->accountIdentifier;

        $accounts = [[
            'accountIdentifier' => $originalAccountIdentifier,
            'name' => $originalAccount->name,
            'delegationIdentifier' => null,
        ]];
        $delegations = Delegation::query()
            ->where('delegate_account_id', $originalAccountIdentifier)
            ->where('status', DelegationStatus::APPROVED->value)
            ->withWhereHas('delegatorAccount', static fn ($query) => $query->where('status', AccountStatus::ACTIVE->value))
            ->orderBy('id')
            ->get();
        foreach ($delegations as $delegation) {
            $account = $delegation->delegatorAccount;
            if ($account === null || ! $this->authorizationAccountService->allowsDelegatedTarget(
                $originalAccount,
                new AccountIdentifier($delegation->delegator_account_id),
                new DelegationIdentifier($delegation->id),
            )) {
                continue;
            }
            $accounts[] = [
                'accountIdentifier' => $delegation->delegator_account_id,
                'name' => $account->name,
                'delegationIdentifier' => $delegation->id,
            ];
        }

        return new AuthorizationRequestReadModel($pending, $clientName, $originalAccountIdentifier, $accounts);
    }
}
