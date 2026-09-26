<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CompleteStepUpWithPasskey;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\CompleteStepUpWithPasskey\CompleteStepUpWithPasskeyInput;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class CompleteStepUpWithPasskeyInputTest extends TestCase
{
    public function testItPreservesValues(): void
    {
        $identity = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174001');
        $key = new ChallengeSessionKey('123e4567-e89b-72d3-a456-426614174002');
        $credential = new WebAuthnCredentialId('Y3JlZGVudGlhbA');
        $input = new CompleteStepUpWithPasskeyInput($identity, $key, $credential, '{}');
        $this->assertSame($identity, $input->identityIdentifier());
        $this->assertSame($key, $input->challengeKey());
        $this->assertSame($credential, $input->credentialId());
        $this->assertSame('{}', $input->responseJson());
    }
}
