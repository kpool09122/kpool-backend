<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\WebAuthn;

readonly class RegistrationVerificationInput
{
    public function __construct(
        public string $responseJson,
        public string $optionsJson,
    ) {
    }
}
