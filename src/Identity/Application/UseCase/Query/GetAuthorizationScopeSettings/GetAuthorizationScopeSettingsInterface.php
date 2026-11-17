<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\GetAuthorizationScopeSettings;

interface GetAuthorizationScopeSettingsInterface
{
    public function process(GetAuthorizationScopeSettingsInputPort $input): AuthorizationScopeSettingsReadModel;
}
