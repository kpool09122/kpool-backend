<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SwitchIdentity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\SwitchIdentity\SwitchIdentityOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;

class SwitchIdentityOutputTest extends TestCase
{
    public function testToArrayReturnsEmptyWhenIdentityIsNull(): void
    {
        $output = new SwitchIdentityOutput();

        $this->assertSame([], $output->toArray());
    }

    public function testToArrayReturnsIdentityDataWithDelegation(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $originalIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $userName = new UserName('delegated-user');
        $email = new Email('user@example.com');
        $language = Language::KOREAN;
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
            [],
            $delegationIdentifier,
            $originalIdentityIdentifier,
        );

        $output = new SwitchIdentityOutput();
        $output->setIdentity($identity);

        $result = $output->toArray();

        $this->assertSame((string) $identityIdentifier, $result['identityIdentifier']);
        $this->assertSame((string) $userName, $result['username']);
        $this->assertSame((string) $email, $result['email']);
        $this->assertSame($language->value, $result['language']);
        $this->assertSame((string) $profileImage, $result['profileImage']);
        $this->assertTrue($result['isDelegated']);
    }

    public function testToArrayReturnsIdentityDataWithoutDelegation(): void
    {
        $identity = new Identity(
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new UserName('test-user'),
            new Email('user@example.com'),
            Language::KOREAN,
            new ImagePath('/resources/path/test.png'),
            HashedPassword::fromPlain(new PlainPassword('PlainPass1!')),
            new DateTimeImmutable(),
        );

        $output = new SwitchIdentityOutput();
        $output->setIdentity($identity);

        $this->assertFalse($output->toArray()['isDelegated']);
    }
}
