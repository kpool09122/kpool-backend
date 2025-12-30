<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;

class IdentityTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUlid());
        $userName = new UserName('test-user');
        $email = new Email('user@example.com');
        $profileImage = new ImagePath('/resources/path/test.png');
        $language = Language::JAPANESE;
        $plainPassword = new PlainPassword('PlainPass1!');
        $hashedPassword = HashedPassword::fromPlain($plainPassword);
        $verifiedAt = new DateTimeImmutable();
        $socialConnection = new SocialConnection(SocialProvider::GOOGLE, 'provider-user-id');
        $connections = [$socialConnection];

        $identity = new Identity($identityIdentifier, $userName, $email, $language, $profileImage, $hashedPassword, $verifiedAt, $connections);

        $this->assertSame($identityIdentifier, $identity->identityIdentifier());
        $this->assertSame($userName, $identity->username());
        $this->assertSame($email, $identity->email());
        $this->assertSame($language, $identity->language());
        $this->assertSame($profileImage, $identity->profileImage());
        $this->assertSame($hashedPassword, $identity->hashedPassword());
        $this->assertSame($verifiedAt, $identity->emailVerifiedAt());
        $this->assertSame($connections, $identity->socialConnections());
    }

    /**
     * 異常系: メールアドレスが認証されていない時、例外がスローされること.
     *
     * @return void
     */
    public function testIsEmailVerifiedThrowsWhenNotVerified(): void
    {
        $identity = $this->createIdentity(verifiedAt: null);

        $this->expectException(DomainException::class);

        $identity->isEmailVerified();
    }

    /**
     * 正常系: 入力されたPasswordのハッシュ値が一致しない場合、例外がスローされること.
     *
     * @return void
     */
    public function testVerifyPasswordThrowsWhenPasswordDoesNotMatch(): void
    {
        $identity = $this->createIdentity();

        $this->expectException(DomainException::class);

        $identity->verifyPassword(new PlainPassword('WrongPass1!'));
    }

    /**
     * 正常系: ソーシャルコネクションを正しく追加できること.
     *
     * @return void
     */
    public function testAddSocialConnection(): void
    {
        $connection = new SocialConnection(SocialProvider::INSTAGRAM, 'provider-user-id');
        $identity = $this->createIdentity();
        $identity->addSocialConnection($connection);
        $this->assertContains($connection, $identity->socialConnections());
    }

    /**
     * 異常系: 重複するソーシャルコネクションを追加しようとすると、例外がスローされること.
     *
     * @return void
     */
    public function testConnectionWhenAddingDuplicateSocialConnection(): void
    {
        $identity = $this->createIdentity();
        $this->expectException(DomainException::class);
        $identity->addSocialConnection(new SocialConnection(SocialProvider::GOOGLE, 'provider-user-id'));
    }

    /**
     * @param IdentityIdentifier|null $identityIdentifier
     * @param UserName|null $userName
     * @param Email|null $email
     * @param Language|null $language
     * @param ImagePath|null $profileImage
     * @param HashedPassword|null $hashedPassword
     * @param DateTimeImmutable|null $verifiedAt
     * @param SocialConnection[] $connections
     * @return Identity
     */
    private function createIdentity(
        ?IdentityIdentifier $identityIdentifier = null,
        ?UserName           $userName = null,
        ?Email              $email = null,
        ?Language           $language = null,
        ?ImagePath          $profileImage = null,
        ?HashedPassword     $hashedPassword = null,
        ?DateTimeImmutable  $verifiedAt = null,
        array               $connections = []
    ): Identity {
        return new Identity(
            $identityIdentifier ?? new IdentityIdentifier(StrTestHelper::generateUlid()),
            $userName ?? new UserName('test-user'),
            $email ?? new Email('user@example.com'),
            $language ?? Language::JAPANESE,
            $profileImage ?? new ImagePath('/resources/path/test.png'),
            $hashedPassword ?? HashedPassword::fromPlain(new PlainPassword('PlainPass1!')),
            $verifiedAt,
            $connections ?: [new SocialConnection(SocialProvider::GOOGLE, 'provider-user-id')]
        );
    }
}
