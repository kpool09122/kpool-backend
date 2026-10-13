<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings;

use InvalidArgumentException;
use Source\Account\Notification\Application\Service\NotificationSettingsAuthorization;
use Source\Account\Notification\Domain\Entity\NotificationSettings;
use Source\Account\Notification\Domain\Repository\NotificationSettingsRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;

readonly class UpdateNotificationSettings implements UpdateNotificationSettingsInterface
{
    public function __construct(
        private NotificationSettingsAuthorization $notificationSettingsAuthorization,
        private PrincipalRepositoryInterface $principalRepository,
        private NotificationSettingsRepositoryInterface $notificationSettingsRepository,
    ) {
    }

    public function process(UpdateNotificationSettingsInputPort $input, UpdateNotificationSettingsOutputPort $output): void
    {
        $this->notificationSettingsAuthorization->assertAllowed($input->accountIdentifier(), $input->principal());
        $settings = new NotificationSettings($input->accountIdentifier(), $input->identityIdentifiers());
        $members = [];
        foreach ($this->principalRepository->findByAccountId($input->accountIdentifier()) as $principal) {
            $members[(string) $principal->identityIdentifier()] = true;
        }
        foreach ($settings->identityIdentifiers() as $identityIdentifier) {
            if (! isset($members[(string) $identityIdentifier])) {
                throw new InvalidArgumentException('Notification recipients must belong to the account.');
            }
        }
        $this->notificationSettingsRepository->save($settings);
    }
}
