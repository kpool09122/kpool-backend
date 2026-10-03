<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\VerifySocialLinkingEmail;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Source\Identity\Application\Service\SocialLinking\SocialLinkingSession;
use Source\Identity\Application\Service\SocialLinking\SocialLinkingSessionStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail\VerifySocialLinkingEmail;
use Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail\VerifySocialLinkingEmailInput;
use Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail\VerifySocialLinkingEmailOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\SocialLinkingVerificationFailedException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class VerifySocialLinkingEmailTest extends TestCase
{
    #[DataProvider('scenarios')]
    public function testCompletion(string $scenario): void
    {
        $id = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $email = new Email('user@example.com');
        $connection = new SocialConnection(SocialProvider::GOOGLE, 'verified-provider-user');
        $session = new SocialLinkingSession($id, $email, $connection, '/mypage/wiki', new DateTimeImmutable('+10 minutes'));
        $identity = new Identity($id, new IdentityName('User'), $scenario === 'changed email' ? new Email('changed@example.com') : $email, Language::ENGLISH, null, null, $scenario === 'existing provider' ? [new SocialConnection(SocialProvider::GOOGLE, 'another-provider-user')] : []);
        /** @var MockInterface&SocialLinkingSessionStorageServiceInterface $sessions */
        $sessions = Mockery::mock(SocialLinkingSessionStorageServiceInterface::class);
        $sessions->shouldReceive('verifyAndConsume')->once()->with(Mockery::type(AuthCode::class))->andReturn($session);
        /** @var MockInterface&IdentityRepositoryInterface $identities */
        $identities = Mockery::mock(IdentityRepositoryInterface::class);
        $identities->shouldReceive('findById')->once()->with($id)->andReturn($scenario === 'deleted identity' ? null : $identity);
        if (! in_array($scenario, ['changed email', 'deleted identity'], true)) {
            $identities->shouldReceive('findBySocialConnection')->once()->with(SocialProvider::GOOGLE, 'verified-provider-user')->andReturn($scenario === 'connection claimed' ? $identity : null);
        }
        $saved = false;
        if (in_array($scenario, ['success', 'existing provider', 'save failed'], true)) {
            $identities->shouldReceive('save')->once()->with($identity)->andReturnUsing(function (Identity $savedIdentity) use ($connection, $scenario, &$saved): void {
                $this->assertTrue($savedIdentity->hasSocialConnection($connection));
                if ($scenario === 'save failed') {
                    throw new RuntimeException('save failed');
                }
                $saved = true;
            });
        } else {
            $identities->shouldNotReceive('save');
        }
        /** @var MockInterface&AuthServiceInterface $auth */
        $auth = Mockery::mock(AuthServiceInterface::class);
        if (in_array($scenario, ['success', 'existing provider'], true)) {
            $auth->shouldReceive('login')->once()->with($identity)->andReturnUsing(function (Identity $identity) use (&$saved): Identity {
                $this->assertTrue($saved);

                return $identity;
            });
        } else {
            $auth->shouldNotReceive('login');
            $this->expectException(in_array($scenario, ['save failed'], true) ? RuntimeException::class : SocialLinkingVerificationFailedException::class);
        }
        $output = new VerifySocialLinkingEmailOutput();
        (new VerifySocialLinkingEmail($sessions, $identities, $auth))->process(new VerifySocialLinkingEmailInput(new AuthCode('123456')), $output);
        $this->assertSame(['redirectUrl' => '/mypage/wiki'], $output->toArray());
        $this->assertCount($scenario === 'existing provider' ? 2 : 1, $identity->socialConnections());
    }

    /** @return array<string, array{string}> */
    public static function scenarios(): array
    {
        return array_combine($names = ['success', 'changed email', 'deleted identity', 'connection claimed', 'existing provider', 'save failed'], array_map(static fn (string $name): array => [$name], $names));
    }

    public function testInvalidCodeDoesNotReadSaveOrLogin(): void
    {
        /** @var MockInterface&SocialLinkingSessionStorageServiceInterface $sessions */
        $sessions = Mockery::mock(SocialLinkingSessionStorageServiceInterface::class);
        $sessions->shouldReceive('verifyAndConsume')->once()->andThrow(new SocialLinkingVerificationFailedException());
        /** @var MockInterface&IdentityRepositoryInterface $identities */
        $identities = Mockery::mock(IdentityRepositoryInterface::class);
        $identities->shouldNotReceive('findById', 'findBySocialConnection', 'save');
        /** @var MockInterface&AuthServiceInterface $auth */
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldNotReceive('login');
        $this->expectException(SocialLinkingVerificationFailedException::class);
        (new VerifySocialLinkingEmail($sessions, $identities, $auth))->process(new VerifySocialLinkingEmailInput(new AuthCode('123456')), new VerifySocialLinkingEmailOutput());
    }
}
