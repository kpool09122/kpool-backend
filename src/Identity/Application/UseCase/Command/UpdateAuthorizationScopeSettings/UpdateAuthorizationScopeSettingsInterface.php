<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings;

use InvalidArgumentException;

interface UpdateAuthorizationScopeSettingsInterface
{
    /** @throws InvalidArgumentException */
    public function process(UpdateAuthorizationScopeSettingsInputPort $input, UpdateAuthorizationScopeSettingsOutputPort $output): void;
}
