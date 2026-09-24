<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\AuthenticateWithPasskey;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\AuthenticateWithPasskey\AuthenticateWithPasskeyInput;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Tests\Helper\StrTestHelper;

class AuthenticateWithPasskeyInputTest extends TestCase
{
    public function test__construct(): void
    {
        $challengeKey = new ChallengeSessionKey(StrTestHelper::generateUuid());
        $credentialId = new WebAuthnCredentialId('Y3JlZGVudGlhbA');
        $responseJson = '{"id":"Y3JlZGVudGlhbA"}';

        $input = new AuthenticateWithPasskeyInput($challengeKey, $credentialId, $responseJson);

        $this->assertSame($challengeKey, $input->challengeKey());
        $this->assertSame($credentialId, $input->credentialId());
        $this->assertSame($responseJson, $input->responseJson());
    }
}
