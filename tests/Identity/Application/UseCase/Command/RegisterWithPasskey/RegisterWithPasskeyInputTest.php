<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\RegisterWithPasskey;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\RegisterWithPasskey\RegisterWithPasskeyInput;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;

class RegisterWithPasskeyInputTest extends TestCase
{
    public function testItReturnsRegistrationInputValues(): void
    {
        $challengeKey = new ChallengeSessionKey(StrTestHelper::generateUuid());
        $identityName = new IdentityName('Passkey User');
        $language = Language::ENGLISH;
        $displayName = new PasskeyDisplayName('Personal passkey');
        $responseJson = '{"id":"Y3JlZGVudGlhbA"}';
        $base64EncodedImage = 'data:image/png;base64,aW1hZ2U=';

        $input = new RegisterWithPasskeyInput(
            $challengeKey,
            $identityName,
            $language,
            $displayName,
            $responseJson,
            $base64EncodedImage,
        );

        $this->assertSame($challengeKey, $input->challengeKey());
        $this->assertSame($identityName, $input->identityName());
        $this->assertSame($language, $input->language());
        $this->assertSame($displayName, $input->displayName());
        $this->assertSame($responseJson, $input->responseJson());
        $this->assertSame($base64EncodedImage, $input->base64EncodedImage());
    }
}
