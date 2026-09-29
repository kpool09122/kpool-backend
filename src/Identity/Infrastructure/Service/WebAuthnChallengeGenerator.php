<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Source\Identity\Domain\Service\WebAuthnChallengeGeneratorInterface;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;

class WebAuthnChallengeGenerator implements WebAuthnChallengeGeneratorInterface
{
    public function generate(): WebAuthnChallenge
    {
        $bytes = random_bytes(WebAuthnChallenge::MIN_BYTE_LENGTH);

        return new WebAuthnChallenge(rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='));
    }
}
