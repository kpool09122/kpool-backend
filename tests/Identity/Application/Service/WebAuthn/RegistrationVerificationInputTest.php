<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service\WebAuthn;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\WebAuthn\RegistrationVerificationInput;

class RegistrationVerificationInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $responseJson = '{"id":"credential"}';
        $optionsJson = '{"challenge":"challenge"}';

        $subject = new RegistrationVerificationInput($responseJson, $optionsJson);

        $this->assertSame($responseJson, $subject->responseJson);
        $this->assertSame($optionsJson, $subject->optionsJson);
    }
}
