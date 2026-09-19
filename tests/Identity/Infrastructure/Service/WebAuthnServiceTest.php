<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\Exception\InvalidPasskeyException;
use Source\Identity\Infrastructure\Service\WebAuthnService;
use Tests\Helper\StrTestHelper;

class WebAuthnServiceTest extends TestCase
{
    public function testRegistrationOptionsRequireDiscoverableCredentialAndUserVerificationWithoutAttachmentRestriction(): void
    {
        $service = new WebAuthnService('example.com', 'k-pool', ['https://example.com']);

        $result = $service->createRegistrationOptions(
            StrTestHelper::generateUuid(),
            'user@example.com',
            'Example User',
        );
        $options = $result->options();

        $this->assertSame('example.com', $options['rp']['id']);
        $this->assertSame('required', $options['authenticatorSelection']['residentKey']);
        $this->assertSame('required', $options['authenticatorSelection']['userVerification']);
        $this->assertNull($options['authenticatorSelection']['authenticatorAttachment'] ?? null);
        $this->assertSame([], $options['excludeCredentials']);
    }

    public function testAuthenticationOptionsUseDiscoverableCredentials(): void
    {
        $service = new WebAuthnService('example.com', 'k-pool', ['https://example.com']);
        $options = $service->createAuthenticationOptions()->options();

        $this->assertSame('example.com', $options['rpId']);
        $this->assertSame('required', $options['userVerification']);
        $this->assertSame([], $options['allowCredentials']);
    }

    public function testInvalidCredentialResponseIsRejected(): void
    {
        $service = new WebAuthnService('example.com', 'k-pool', ['https://example.com']);
        $this->expectException(InvalidPasskeyException::class);

        $service->credentialIdFromResponse('{"invalid":true}');
    }
}
