<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\RecoverPasskey;

use Source\Identity\Application\UseCase\Command\RecoverPasskey\RecoverPasskeyInput;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;
use Tests\TestCase;

class RecoverPasskeyInputTest extends TestCase
{
    public function testItExposesConstructorValues(): void
    {
        $recovery = new PasskeyRecoveryKey('123e4567-e89b-72d3-a456-426614174001');
        $challenge = new ChallengeSessionKey('123e4567-e89b-72d3-a456-426614174002');
        $name = new PasskeyDisplayName('recovered');
        $input = new RecoverPasskeyInput($recovery, $challenge, $name, '{"response":true}');
        $this->assertSame($recovery, $input->recoveryKey());
        $this->assertSame($challenge, $input->challengeKey());
        $this->assertSame($name, $input->displayName());
        $this->assertSame('{"response":true}', $input->responseJson());
    }
}
