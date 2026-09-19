<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Repository;

use Source\Identity\Domain\Entity\PasskeyChallengeSession;

interface PasskeyChallengeSessionRepositoryInterface
{
    public function save(PasskeyChallengeSession $session): void;

    public function consume(string $identifier, string $purpose): PasskeyChallengeSession;
}
