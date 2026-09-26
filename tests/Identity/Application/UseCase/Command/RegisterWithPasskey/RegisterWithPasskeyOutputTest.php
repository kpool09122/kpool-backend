<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\RegisterWithPasskey;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\RegisterWithPasskey\RegisterWithPasskeyOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;

class RegisterWithPasskeyOutputTest extends TestCase
{
    public function testToArrayReturnsEmptyBeforeIdentityIsSet(): void
    {
        $this->assertSame([], (new RegisterWithPasskeyOutput())->toArray());
    }

    public function testToArrayReturnsIdentityAndReturnTo(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $identityName = new IdentityName('Passkey User');
        $email = new Email('passkey@example.com');
        $language = Language::ENGLISH;
        $profileImage = new ImagePath('/images/passkey-user.png');
        $identity = new Identity(
            $identityIdentifier,
            $identityName,
            $email,
            $language,
            $profileImage,
            new DateTimeImmutable(),
        );

        $output = new RegisterWithPasskeyOutput();
        $output->setIdentity($identity, '/dashboard');

        $this->assertSame([
            'identityIdentifier' => (string) $identityIdentifier,
            'identityName' => (string) $identityName,
            'email' => (string) $email,
            'language' => $language->value,
            'profileImage' => (string) $profileImage,
            'returnTo' => '/dashboard',
        ], $output->toArray());
    }

    public function testToArrayReturnsNullOptionalValues(): void
    {
        $identity = new Identity(
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new IdentityName('Passkey User'),
            new Email('passkey@example.com'),
            Language::JAPANESE,
            null,
            new DateTimeImmutable(),
        );
        $output = new RegisterWithPasskeyOutput();
        $output->setIdentity($identity, null);

        $this->assertNull($output->toArray()['profileImage']);
        $this->assertNull($output->toArray()['returnTo']);
    }
}
