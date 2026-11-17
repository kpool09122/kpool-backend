<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings;

readonly class UpdateAuthorizationScopeSettingsOutput implements UpdateAuthorizationScopeSettingsOutputPort
{
    /** @return array{} */
    public function toArray(): array
    {
        return [];
    }
}
