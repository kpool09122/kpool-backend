<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Random\RandomException;
use Source\Identity\Application\Service\OAuthStateGeneratorInterface;
use Source\Identity\Application\Service\OAuthStateSession;
use Source\Identity\Domain\ValueObject\OAuthState;

class OAuthStateGenerator implements OAuthStateGeneratorInterface
{
    private const int STATE_BYTES = 32;
    private const string EXPIRY_INTERVAL = '+10 minutes';

    /**
     * @return OAuthStateSession
     * @throws RandomException
     */
    public function generate(): OAuthStateSession
    {
        $randomBytes = random_bytes(self::STATE_BYTES);
        $state = bin2hex($randomBytes);
        $expiresAt = new DateTimeImmutable(self::EXPIRY_INTERVAL);

        return new OAuthStateSession(new OAuthState($state), $expiresAt);
    }
}
