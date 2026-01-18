<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Service;

use DateTimeImmutable;
use Random\RandomException;
use Source\Identity\Domain\ValueObject\OAuthState;

class OAuthStateGenerator implements OAuthStateGeneratorInterface
{
    private const int STATE_BYTES = 32;
    private const string EXPIRY_INTERVAL = '+10 minutes';

    /**
     * @return OAuthState
     * @throws RandomException
     */
    public function generate(): OAuthState
    {
        $randomBytes = random_bytes(self::STATE_BYTES);
        $state = bin2hex($randomBytes);
        $expiresAt = new DateTimeImmutable(self::EXPIRY_INTERVAL);

        return new OAuthState($state, $expiresAt);
    }
}
