<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings;

use InvalidArgumentException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\NotificationSettings;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class UpdateNotificationSettings implements UpdateNotificationSettingsInterface
{
    public function __construct(
        private PolicyEvaluatorInterface $policyEvaluator,
        private PrincipalRepositoryInterface $principalRepository,
        private AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function process(UpdateNotificationSettingsInputPort $input, UpdateNotificationSettingsOutputPort $output): void
    {
        if ((string) $input->accountIdentifier() !== (string) $input->principal()->accountIdentifier()) {
            throw new AccountUpdateForbiddenException();
        }
        $account = $this->accountRepository->findById($input->accountIdentifier());
        if ($account === null) {
            throw new AccountNotFoundException();
        }
        if (! $this->policyEvaluator->evaluate($input->principal(), Action::NOTIFICATION_SETTINGS_UPDATE, Resource::account($account->accountIdentifier(), $account->type()))) {
            throw new AccountUpdateForbiddenException();
        }
        $settings = new NotificationSettings($input->identityIdentifiers());
        $members = [];
        foreach ($this->principalRepository->findByAccountId($input->accountIdentifier()) as $principal) {
            $members[(string) $principal->identityIdentifier()] = true;
        }
        foreach ($settings->identityIdentifiers() as $identityIdentifier) {
            if (! isset($members[(string) $identityIdentifier])) {
                throw new InvalidArgumentException('Notification recipients must belong to the account.');
            }
        }
        $account->changeNotificationSettings($settings);
        $this->accountRepository->save($account);
    }
}
