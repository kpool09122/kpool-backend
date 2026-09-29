<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\AuthenticateWithPasskey;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\AuthenticateWithPasskey\AuthenticateWithPasskeyOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;

class AuthenticateWithPasskeyOutputTest extends TestCase
{
    public function testToArrayReturnsEmptyWhenIdentityIsNull(): void
    {
        $output = new AuthenticateWithPasskeyOutput();

        $this->assertSame([], $output->toArray());
    }

    public function testToArrayReturnsIdentityData(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $identityName = new IdentityName('test-user');
        $email = new Email('user@example.com');
        $language = Language::JAPANESE;
        $profileImage = new ImagePath('/resources/path/test.png');
        $identity = new Identity(
            $identityIdentifier,
            $identityName,
            $email,
            $language,
            $profileImage,
            new DateTimeImmutable(),
        );

        $output = new AuthenticateWithPasskeyOutput();
        $output->setIdentity($identity);

        $this->assertSame([
            'identityIdentifier' => (string) $identityIdentifier,
            'identityName' => (string) $identityName,
            'email' => (string) $email,
            'language' => $language->value,
            'profileImage' => (string) $profileImage,
        ], $output->toArray());
    }

    public function testToArrayReturnsNullProfileImageWhenNotSet(): void
    {
        $identity = new Identity(
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new IdentityName('test-user'),
            new Email('user@example.com'),
            Language::JAPANESE,
            null,
            new DateTimeImmutable(),
        );
        $output = new AuthenticateWithPasskeyOutput();
        $output->setIdentity($identity);

        $this->assertNull($output->toArray()['profileImage']);
    }
}
