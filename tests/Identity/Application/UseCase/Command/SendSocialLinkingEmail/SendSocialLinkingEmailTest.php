<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SendSocialLinkingEmail;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Identity\Application\Service\EmailSendingStatus;
use Source\Identity\Application\Service\SocialLinking\SocialLinkingSession;
use Source\Identity\Application\Service\SocialLinking\SocialLinkingSessionStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail\SendSocialLinkingEmail;
use Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail\SendSocialLinkingEmailInput;
use Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail\SendSocialLinkingEmailOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\SocialLinkingVerificationFailedException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class SendSocialLinkingEmailTest extends TestCase
{
    #[DataProvider('targets')]
    public function testOnlySendsToUnchangedRegisteredEmail(string $target): void
    {
        $id = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $email = new Email('user@example.com');
        /** @var MockInterface&SocialLinkingSessionStorageServiceInterface $sessions */
        $sessions = Mockery::mock(SocialLinkingSessionStorageServiceInterface::class);
        $sessions->shouldReceive('requireValid')->once()->andReturn(new SocialLinkingSession($id, $email, new SocialConnection(SocialProvider::GOOGLE, 'provider-user'), '/mypage', new DateTimeImmutable('+10 minutes')));
        /** @var MockInterface&IdentityRepositoryInterface $identities */
        $identities = Mockery::mock(IdentityRepositoryInterface::class);
        $identities->shouldReceive('findById')->once()->with($id)->andReturn($target === 'deleted' ? null : new Identity($id, new IdentityName('User'), $target === 'changed' ? new Email('new@example.com') : $email, Language::ENGLISH, null, null));
        if ($target === 'valid') {
            $sessions->shouldReceive('sendCode')->once()->with(Language::JAPANESE)->andReturn(new EmailSendingStatus(true, 4, 60));
        } else {
            $sessions->shouldNotReceive('sendCode');
            $this->expectException(SocialLinkingVerificationFailedException::class);
        }
        $output = new SendSocialLinkingEmailOutput();
        (new SendSocialLinkingEmail($sessions, $identities))->process(new SendSocialLinkingEmailInput(Language::JAPANESE), $output);
        $this->assertSame(['accepted' => true, 'remainingSends' => 4, 'retryAfterSeconds' => 60], $output->toArray());
    }

    /** @return array<string, array{string}> */
    public static function targets(): array
    {
        return ['valid' => ['valid'], 'changed' => ['changed'], 'deleted' => ['deleted']];
    }
}
