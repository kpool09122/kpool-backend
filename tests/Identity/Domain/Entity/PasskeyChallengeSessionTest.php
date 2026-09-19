<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\Entity\PasskeyChallengeSession;
use Source\Identity\Domain\Exception\InvalidPasskeyException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class PasskeyChallengeSessionTest extends TestCase
{
    public function testSessionKeepsPurposeIdentityAndSignupData(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $session = new PasskeyChallengeSession(
            StrTestHelper::generateUuid(),
            PasskeyChallengeSession::PURPOSE_SIGNUP,
            '{"challenge":"value"}',
            $identityIdentifier,
            ['email' => 'user@example.com'],
        );

        $this->assertSame(PasskeyChallengeSession::PURPOSE_SIGNUP, $session->purpose());
        $this->assertSame($identityIdentifier, $session->identityIdentifier());
        $this->assertSame(['email' => 'user@example.com'], $session->signupData());
    }

    public function testUnknownPurposeIsRejected(): void
    {
        $this->expectException(InvalidPasskeyException::class);
        new PasskeyChallengeSession(StrTestHelper::generateUuid(), 'other', '{}');
    }
}
