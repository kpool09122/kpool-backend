<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Domain\Entity\ChallengeSession;
use Source\Identity\Domain\ValueObject\ChallengePurpose;
use Source\Identity\Domain\ValueObject\ChallengeSessionIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface ChallengeSessionStorageServiceInterface
{
    public function store(ChallengeSession $session): void;

    public function consume(
        ChallengeSessionIdentifier $identifier,
        ChallengePurpose $expectedPurpose,
        ?IdentityIdentifier $expectedIdentityIdentifier = null,
    ): ChallengeSession;
}
