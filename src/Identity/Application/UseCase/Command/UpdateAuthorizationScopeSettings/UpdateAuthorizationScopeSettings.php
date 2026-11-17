<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings;

use InvalidArgumentException;
use Source\Identity\Domain\Factory\AuthorizationScopeSettingsFactoryInterface;
use Source\Identity\Domain\Repository\AuthorizationScopeSettingsRepositoryInterface;

readonly class UpdateAuthorizationScopeSettings implements UpdateAuthorizationScopeSettingsInterface
{
    public function __construct(
        private AuthorizationScopeSettingsFactoryInterface $authorizationScopeSettingsFactory,
        private AuthorizationScopeSettingsRepositoryInterface $authorizationScopeSettingsRepository,
    ) {
    }

    /** @throws InvalidArgumentException */
    public function process(UpdateAuthorizationScopeSettingsInputPort $input, UpdateAuthorizationScopeSettingsOutputPort $output): void
    {
        $settings = $this->authorizationScopeSettingsRepository->find($input->identityIdentifier());
        if ($settings === null) {
            $settings = $this->authorizationScopeSettingsFactory->create($input->identityIdentifier(), $input->scopes());
        } else {
            $settings->updateScopes($input->scopes());
        }
        $this->authorizationScopeSettingsRepository->save($settings);
    }
}
