<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Service;

use Source\Identity\Domain\ValueObject\WebAuthnChallenge;

interface WebAuthnChallengeGeneratorInterface
{
    public function generate(): WebAuthnChallenge;
}
