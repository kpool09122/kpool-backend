<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\Login;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\Login\LoginOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;

class LoginOutputTest extends TestCase
{
    public function testToArrayReturnsEmptyWhenIdentityIsNull(): void
    {
        $output = new LoginOutput();

        $this->assertSame([], $output->toArray());
    }

    public function testToArrayReturnsIdentityData(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $userName = new UserName('test-user');
        $email = new Email('user@example.com');
        $language = Language::JAPANESE;
        $profileImage = new ImagePath('/resources/path/test.png');
        $hashedPassword = HashedPassword::fromPlain(new PlainPassword('PlainPass1!'));
        $emailVerifiedAt = new DateTimeImmutable();

        $identity = new Identity(
            $identityIdentifier,
            $userName,
            $email,
            $language,
            $profileImage,
            $hashedPassword,
            $emailVerifiedAt,
        );

        $output = new LoginOutput();
        $output->setIdentity($identity);

        $result = $output->toArray();

        $this->assertSame((string) $identityIdentifier, $result['identityIdentifier']);
        $this->assertSame((string) $userName, $result['username']);
        $this->assertSame((string) $email, $result['email']);
        $this->assertSame($language->value, $result['language']);
        $this->assertSame((string) $profileImage, $result['profileImage']);
    }

    public function testToArrayReturnsNullProfileImageWhenNotSet(): void
    {
        $identity = new Identity(
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new UserName('test-user'),
            new Email('user@example.com'),
            Language::JAPANESE,
            null,
            HashedPassword::fromPlain(new PlainPassword('PlainPass1!')),
            new DateTimeImmutable(),
        );

        $output = new LoginOutput();
        $output->setIdentity($identity);

        $this->assertNull($output->toArray()['profileImage']);
    }
}
