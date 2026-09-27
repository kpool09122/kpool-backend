<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\SocialLinking;

use Source\Identity\Domain\Exception\SocialLinkingSessionInvalidException;
use Source\Identity\Domain\Exception\SocialLinkingVerificationFailedException;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;

interface SocialLinkingSessionStorageServiceInterface
{
    public function issue(IdentityIdentifier $identityIdentifier, Email $email, SocialConnection $connection, string $returnTo): void;

    /** @throws SocialLinkingSessionInvalidException */
    public function requireValid(): SocialLinkingSession;

    /**
     * @throws SocialLinkingSessionInvalidException
     * @throws SocialLinkingVerificationFailedException
     */
    public function sendCode(Language $language): void;

    /**
     * @throws SocialLinkingSessionInvalidException
     * @throws SocialLinkingVerificationFailedException
     */
    public function verifyAndConsume(AuthCode $code): SocialLinkingSession;
}
