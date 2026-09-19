<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\Passkey;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface PasskeyUseCaseInterface
{
    /** @return array<string, mixed> */
    public function beginSignup(string $identityName, string $email, string $language, ?string $base64EncodedImage, ?string $oneTimeToken): array;

    /**
     * @param array<string, mixed> $credential
     * @return array<string, mixed>
     */
    public function finishSignup(string $challengeIdentifier, array $credential, string $displayName): array;

    /** @return array<string, mixed> */
    public function beginLogin(): array;

    /**
     * @param array<string, mixed> $credential
     * @return array<string, mixed>
     */
    public function finishLogin(string $challengeIdentifier, array $credential): array;

    /** @return array<string, mixed> */
    public function beginAdd(IdentityIdentifier $identityIdentifier): array;

    /**
     * @param array<string, mixed> $credential
     * @return array<string, mixed>
     */
    public function finishAdd(IdentityIdentifier $identityIdentifier, string $challengeIdentifier, array $credential, string $displayName): array;

    /** @return array<int, array<string, mixed>> */
    public function list(IdentityIdentifier $identityIdentifier): array;

    public function rename(IdentityIdentifier $identityIdentifier, string $passkeyIdentifier, string $displayName): void;

    public function delete(IdentityIdentifier $identityIdentifier, string $passkeyIdentifier): void;
}
