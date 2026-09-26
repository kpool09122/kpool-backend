<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Application\Service\WebAuthn\AuthenticationOptionsInput;
use Source\Identity\Application\Service\WebAuthn\AuthenticationVerificationInput;
use Source\Identity\Application\Service\WebAuthn\RegistrationOptionsInput;
use Source\Identity\Application\Service\WebAuthn\RegistrationVerificationInput;
use Source\Identity\Application\Service\WebAuthn\VerifiedPasskeyAuthentication;
use Source\Identity\Application\Service\WebAuthn\VerifiedPasskeyCredential;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;

interface WebAuthnServiceInterface
{
    public function createRegistrationOptions(RegistrationOptionsInput $input): WebAuthnOptions;

    public function verifyRegistration(RegistrationVerificationInput $input): VerifiedPasskeyCredential;

    public function createAuthenticationOptions(AuthenticationOptionsInput $input): WebAuthnOptions;

    public function verifyAuthentication(AuthenticationVerificationInput $input): VerifiedPasskeyAuthentication;
}
