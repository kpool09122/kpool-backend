<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\SocialConnectionAlreadyExistsException;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;

class IdentityTest extends TestCase
{
    public function testIdentityContainsProfileButNoAuthenticationCredential(): void
    {
        $identifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $name = new IdentityName('test-user');
        $email = new Email('user@example.com');
        $image = new ImagePath('/resources/path/test.png');
        $verifiedAt = new DateTimeImmutable();
        $connection = new SocialConnection(SocialProvider::GOOGLE, 'provider-user-id');

        $identity = new Identity($identifier, $name, $email, Language::JAPANESE, $image, $verifiedAt, [$connection]);

        $this->assertSame($identifier, $identity->identityIdentifier());
        $this->assertSame($name, $identity->identityName());
        $this->assertSame($email, $identity->email());
        $this->assertSame(Language::JAPANESE, $identity->language());
        $this->assertSame($image, $identity->profileImage());
        $this->assertSame($verifiedAt, $identity->emailVerifiedAt());
        $this->assertSame([$connection], $identity->socialConnections());
    }

    public function testAddSocialConnection(): void
    {
        $identity = $this->createIdentity();
        $connection = new SocialConnection(SocialProvider::KAKAO, 'provider-user-id');

        $identity->addSocialConnection($connection);

        $this->assertContains($connection, $identity->socialConnections());
    }

    public function testAddingDuplicateSocialConnectionIsRejected(): void
    {
        $identity = $this->createIdentity();
        $this->expectException(SocialConnectionAlreadyExistsException::class);

        $identity->addSocialConnection(new SocialConnection(SocialProvider::GOOGLE, 'provider-user-id'));
    }

    private function createIdentity(): Identity
    {
        return new Identity(
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new IdentityName('test-user'),
            new Email('user@example.com'),
            Language::JAPANESE,
            null,
            null,
            [new SocialConnection(SocialProvider::GOOGLE, 'provider-user-id')],
        );
    }
}
