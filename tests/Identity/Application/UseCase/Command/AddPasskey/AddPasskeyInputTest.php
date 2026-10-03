<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\AddPasskey;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\AddPasskey\AddPasskeyInput;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class AddPasskeyInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $challengeKey = new ChallengeSessionKey('019c9b4c-0000-7000-8000-000000000001');
        $displayName = new PasskeyDisplayName('Laptop');
        $responseJson = '{"id":"credential"}';

        $subject = new AddPasskeyInput($identityIdentifier, $challengeKey, $displayName, $responseJson);

        $this->assertSame($identityIdentifier, $subject->identityIdentifier());
        $this->assertSame($challengeKey, $subject->challengeKey());
        $this->assertSame($displayName, $subject->displayName());
        $this->assertSame($responseJson, $subject->responseJson());
    }
}
