<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Repository;

use DateTimeImmutable;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SignupSession;

interface SignupSessionRepositoryInterface
{
    public function store(OAuthState $state, SignupSession $session, DateTimeImmutable $expiresAt): void;

    public function find(OAuthState $state): ?SignupSession;

    public function delete(OAuthState $state): void;
}
