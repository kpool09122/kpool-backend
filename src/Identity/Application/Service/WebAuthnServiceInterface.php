<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

interface WebAuthnServiceInterface
{
    /** @param string[] $excludedCredentialIds */
    public function createRegistrationOptions(
        string $userHandle,
        string $userName,
        string $displayName,
        array $excludedCredentialIds = [],
    ): WebAuthnOptions;

    public function verifyRegistration(string $credentialJson, string $optionsJson): VerifiedPasskey;

    public function createAuthenticationOptions(): WebAuthnOptions;

    public function credentialIdFromResponse(string $credentialJson): string;

    public function verifyAuthentication(
        string $credentialJson,
        string $optionsJson,
        string $credentialSource,
    ): VerifiedPasskey;
}
