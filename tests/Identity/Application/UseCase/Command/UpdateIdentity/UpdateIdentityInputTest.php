<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\UpdateIdentity;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\UpdateIdentity\UpdateIdentityInput;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;

class UpdateIdentityInputTest extends TestCase
{
    public function test__construct(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $originalIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $identityName = new IdentityName('updated-user');
        $language = Language::KOREAN;
        $base64EncodedImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';

        $input = new UpdateIdentityInput(
            $identityIdentifier,
            $delegationIdentifier,
            $originalIdentityIdentifier,
            $identityName,
            $language,
            $base64EncodedImage,
            true,
        );

        $this->assertSame($identityIdentifier, $input->identityIdentifier());
        $this->assertSame($delegationIdentifier, $input->delegationIdentifier());
        $this->assertSame($originalIdentityIdentifier, $input->originalIdentityIdentifier());
        $this->assertSame($identityName, $input->identityName());
        $this->assertSame($language, $input->language());
        $this->assertSame($base64EncodedImage, $input->base64EncodedImage());
        $this->assertTrue($input->profileImageProvided());
    }

    public function testNullableValuesCanBeRetrieved(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new UpdateIdentityInput(
            $identityIdentifier,
            null,
            null,
            null,
            null,
            null,
            false,
        );

        $this->assertSame($identityIdentifier, $input->identityIdentifier());
        $this->assertNull($input->delegationIdentifier());
        $this->assertNull($input->originalIdentityIdentifier());
        $this->assertNull($input->identityName());
        $this->assertNull($input->language());
        $this->assertNull($input->base64EncodedImage());
        $this->assertFalse($input->profileImageProvided());
    }

    public function testProfileImageCanBeExplicitlyProvidedAsNull(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new UpdateIdentityInput(
            $identityIdentifier,
            null,
            null,
            null,
            null,
            null,
            true,
        );

        $this->assertNull($input->base64EncodedImage());
        $this->assertTrue($input->profileImageProvided());
    }
}
