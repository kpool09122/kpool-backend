<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\UpdateIdentity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\UpdateIdentity\UpdateIdentityOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;

class UpdateIdentityOutputTest extends TestCase
{
    public function testToArrayReturnsEmptyWhenIdentityIsNull(): void
    {
        $output = new UpdateIdentityOutput();

        $this->assertSame([], $output->toArray());
    }

    public function testToArrayReturnsIdentityData(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $identityName = new IdentityName('updated-user');
        $email = new Email('user@example.com');
        $language = Language::KOREAN;
        $profileImage = new ImagePath('/resources/path/updated.png');
        $hashedPassword = HashedPassword::fromPlain(new PlainPassword('PlainPass1!'));
        $emailVerifiedAt = new DateTimeImmutable();

        $identity = new Identity(
            $identityIdentifier,
            $identityName,
            $email,
            $language,
            $profileImage,
            $hashedPassword,
            $emailVerifiedAt,
        );

        $output = new UpdateIdentityOutput();
        $output->setIdentity($identity);

        $result = $output->toArray();

        $this->assertSame((string) $identityIdentifier, $result['identityIdentifier']);
        $this->assertSame((string) $identityName, $result['identityName']);
        $this->assertSame((string) $email, $result['email']);
        $this->assertSame($language->value, $result['language']);
        $this->assertSame((string) $profileImage, $result['profileImage']);
    }

    public function testToArrayReturnsNullProfileImageWhenIdentityHasNoProfileImage(): void
    {
        $identity = new Identity(
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new IdentityName('updated-user'),
            new Email('user@example.com'),
            Language::JAPANESE,
            null,
            HashedPassword::fromPlain(new PlainPassword('PlainPass1!')),
            new DateTimeImmutable(),
        );

        $output = new UpdateIdentityOutput();
        $output->setIdentity($identity);

        $this->assertNull($output->toArray()['profileImage']);
    }
}
