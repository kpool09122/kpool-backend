<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\WebAuthn;

use Source\Identity\Domain\ValueObject\CredentialSource;

readonly class AuthenticationVerificationInput
{
    public function __construct(
        public string $responseJson,
        public string $optionsJson,
        public CredentialSource $credentialSource,
        public ?string $expectedUserHandle,
    ) {
    }
}
