<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SocialLogin\Callback;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use RuntimeException;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallback;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInput;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInterface;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Event\IdentityCreated;
use Source\Identity\Domain\Event\IdentityCreatedViaInvitation;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Repository\SignupSessionRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\OAuthCode;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProfile;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SocialLoginCallbackTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $socialOAuthClient = Mockery::mock(SocialOAuthServiceInterface::class);
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $signupSessionRepository = Mockery::mock(SignupSessionRepositoryInterface::class);
        $authService = Mockery::mock(AuthServiceInterface::class);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthServiceInterface::class, $socialOAuthClient);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(SignupSessionRepositoryInterface::class, $signupSessionRepository);
        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(SocialLoginCallbackInterface::class);

        $this->assertInstanceOf(SocialLoginCallback::class, $useCase);
    }

    /**
     * 正常系: 既にソーシャル連携済みならそのユーザーでログインすること（イベント発行なし）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWhenSocialConnectionExists(): void
    {
        $provider = SocialProvider::GOOGLE;
        $code = new OAuthCode('code');
        $state = new OAuthState('state-token', new DateTimeImmutable('+10 minutes'));
        $input = new SocialLoginCallbackInput($provider, $code, $state);
        $output = new SocialLoginCallbackOutput();

        $email = new Email('user@example.com');
        $profile = new SocialProfile($provider, 'provider-user-1', $email, 'Example User');
        $identity = $this->createIdentity($email, connections: [new SocialConnection($provider, $profile->providerUserId())]);

        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $oauthStateRepository->shouldReceive('consume')
            ->once()
            ->with($state)
            ->andReturnNull();

        $socialOAuthClient = Mockery::mock(SocialOAuthServiceInterface::class);
        $socialOAuthClient->shouldReceive('fetchProfile')
            ->once()
            ->with($provider, $code)
            ->andReturn($profile);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findBySocialConnection')
            ->once()
            ->with($provider, $profile->providerUserId())
            ->andReturn($identity);
        $identityRepository->shouldNotReceive('findByEmail');
        $identityRepository->shouldNotReceive('save');

        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $identityFactory->shouldNotReceive('createFromSocialProfile');

        $signupSessionRepository = Mockery::mock(SignupSessionRepositoryInterface::class);
        $signupSessionRepository->shouldNotReceive('find');
        $signupSessionRepository->shouldNotReceive('delete');

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('login')
            ->once()
            ->with($identity)
            ->andReturn($identity);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldNotReceive('dispatch');

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthServiceInterface::class, $socialOAuthClient);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(SignupSessionRepositoryInterface::class, $signupSessionRepository);
        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(SocialLoginCallbackInterface::class);

        $useCase->process($input, $output);

        $this->assertSame('/auth/callback', $output->redirectUrl());
    }

    /**
     * 正常系: メール一致のユーザーがいれば連携追加してログインすること（イベント発行なし）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWhenUserWithSameEmailExists(): void
    {
        $provider = SocialProvider::LINE;
        $code = new OAuthCode('code');
        $state = new OAuthState('state-token', new DateTimeImmutable('+10 minutes'));
        $input = new SocialLoginCallbackInput($provider, $code, $state);
        $output = new SocialLoginCallbackOutput();

        $email = new Email('line-user@example.com');
        $profile = new SocialProfile($provider, 'provider-user-2', $email, 'Line User');
        $existingUser = $this->createIdentity($email);

        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $oauthStateRepository->shouldReceive('consume')
            ->once()
            ->with($state)
            ->andReturnNull();

        $socialOAuthClient = Mockery::mock(SocialOAuthServiceInterface::class);
        $socialOAuthClient->shouldReceive('fetchProfile')
            ->once()
            ->with($provider, $code)
            ->andReturn($profile);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findBySocialConnection')
            ->once()
            ->with($provider, $profile->providerUserId())
            ->andReturnNull();
        $identityRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($existingUser);
        $identityRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (Identity $identity): bool => $identity->hasSocialConnection(new SocialConnection($provider, $profile->providerUserId()))))
            ->andReturnNull();

        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $identityFactory->shouldNotReceive('createFromSocialProfile');

        $signupSessionRepository = Mockery::mock(SignupSessionRepositoryInterface::class);
        $signupSessionRepository->shouldNotReceive('find');
        $signupSessionRepository->shouldNotReceive('delete');

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('login')
            ->once()
            ->with($existingUser)
            ->andReturn($existingUser);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldNotReceive('dispatch');

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthServiceInterface::class, $socialOAuthClient);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(SignupSessionRepositoryInterface::class, $signupSessionRepository);
        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(SocialLoginCallbackInterface::class);

        $useCase->process($input, $output);

        $this->assertSame('/auth/callback', $output->redirectUrl());
    }

    /**
     * 正常系: 初回ログイン時はユーザー作成とIdentityCreatedイベント発行を行うこと（SignupSessionあり）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWhenUserNotFoundCreatesNewUserWithSignupSession(): void
    {
        $provider = SocialProvider::KAKAO;
        $code = new OAuthCode('code');
        $state = new OAuthState('state-token', new DateTimeImmutable('+10 minutes'));
        $input = new SocialLoginCallbackInput($provider, $code, $state);
        $output = new SocialLoginCallbackOutput();

        $email = new Email('kakao-user@example.com');
        $profile = new SocialProfile($provider, 'provider-user-3', $email, 'Kakao User');
        $newUser = $this->createIdentity($email);
        $signupSession = new SignupSession(AccountType::CORPORATION);

        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $oauthStateRepository->shouldReceive('consume')
            ->once()
            ->with($state)
            ->andReturnNull();

        $socialOAuthClient = Mockery::mock(SocialOAuthServiceInterface::class);
        $socialOAuthClient->shouldReceive('fetchProfile')
            ->once()
            ->with($provider, $code)
            ->andReturn($profile);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findBySocialConnection')
            ->once()
            ->with($provider, $profile->providerUserId())
            ->andReturnNull();
        $identityRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturnNull();
        $identityRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (Identity $identity): bool => (string)$identity->identityIdentifier() === (string)$newUser->identityIdentifier()
                && $identity->hasSocialConnection(new SocialConnection($provider, $profile->providerUserId()))))
            ->andReturnNull();

        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $identityFactory->shouldReceive('createFromSocialProfile')
            ->once()
            ->with($profile)
            ->andReturn($newUser);

        $signupSessionRepository = Mockery::mock(SignupSessionRepositoryInterface::class);
        $signupSessionRepository->shouldReceive('find')
            ->once()
            ->with($state)
            ->andReturn($signupSession);
        $signupSessionRepository->shouldReceive('delete')
            ->once()
            ->with($state)
            ->andReturnNull();

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('login')
            ->once()
            ->with($newUser)
            ->andReturn($newUser);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(
                fn ($event) => $event instanceof IdentityCreated
                    && (string) $event->identityIdentifier === (string) $newUser->identityIdentifier()
                    && (string) $event->email === (string) $email
                    && $event->accountType === AccountType::CORPORATION
                    && $event->name === $profile->name()
            ));

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthServiceInterface::class, $socialOAuthClient);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(SignupSessionRepositoryInterface::class, $signupSessionRepository);
        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(SocialLoginCallbackInterface::class);

        $useCase->process($input, $output);

        $this->assertSame('/auth/callback', $output->redirectUrl());
    }

    /**
     * 正常系: 初回ログイン時、SignupSessionにinvitationTokenがあればIdentityCreatedViaInvitationイベントを発行すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWhenUserNotFoundWithInvitationToken(): void
    {
        $provider = SocialProvider::GOOGLE;
        $code = new OAuthCode('code');
        $state = new OAuthState('state-token', new DateTimeImmutable('+10 minutes'));
        $input = new SocialLoginCallbackInput($provider, $code, $state);
        $output = new SocialLoginCallbackOutput();

        $email = new Email('invited-user@example.com');
        $profile = new SocialProfile($provider, 'invited-provider-user', $email, 'Invited User');
        $newUser = $this->createIdentity($email);
        $invitationToken = new InvitationToken(bin2hex(random_bytes(32)));
        $signupSession = new SignupSession(null, $invitationToken);

        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $oauthStateRepository->shouldReceive('consume')
            ->once()
            ->with($state)
            ->andReturnNull();

        $socialOAuthClient = Mockery::mock(SocialOAuthServiceInterface::class);
        $socialOAuthClient->shouldReceive('fetchProfile')
            ->once()
            ->with($provider, $code)
            ->andReturn($profile);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findBySocialConnection')
            ->once()
            ->with($provider, $profile->providerUserId())
            ->andReturnNull();
        $identityRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturnNull();
        $identityRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (Identity $identity): bool => (string) $identity->identityIdentifier() === (string) $newUser->identityIdentifier()
                && $identity->hasSocialConnection(new SocialConnection($provider, $profile->providerUserId()))))
            ->andReturnNull();

        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $identityFactory->shouldReceive('createFromSocialProfile')
            ->once()
            ->with($profile)
            ->andReturn($newUser);

        $signupSessionRepository = Mockery::mock(SignupSessionRepositoryInterface::class);
        $signupSessionRepository->shouldReceive('find')
            ->once()
            ->with($state)
            ->andReturn($signupSession);
        $signupSessionRepository->shouldReceive('delete')
            ->once()
            ->with($state)
            ->andReturnNull();

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('login')
            ->once()
            ->with($newUser)
            ->andReturn($newUser);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(
                fn ($event) => $event instanceof IdentityCreatedViaInvitation
                    && (string) $event->identityIdentifier === (string) $newUser->identityIdentifier()
                    && (string) $event->invitationToken === (string) $invitationToken
            ));

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthServiceInterface::class, $socialOAuthClient);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(SignupSessionRepositoryInterface::class, $signupSessionRepository);
        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(SocialLoginCallbackInterface::class);

        $useCase->process($input, $output);

        $this->assertSame('/auth/callback', $output->redirectUrl());
    }

    /**
     * 正常系: 初回ログイン時、SignupSessionがなければデフォルトでINDIVIDUALとしてイベント発行すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWhenUserNotFoundCreatesNewUserWithoutSignupSession(): void
    {
        $provider = SocialProvider::GOOGLE;
        $code = new OAuthCode('code');
        $state = new OAuthState('state-token', new DateTimeImmutable('+10 minutes'));
        $input = new SocialLoginCallbackInput($provider, $code, $state);
        $output = new SocialLoginCallbackOutput();

        $email = new Email('new-google-user@example.com');
        $profile = new SocialProfile($provider, 'new-provider-user', $email, 'New User');
        $newUser = $this->createIdentity($email);

        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $oauthStateRepository->shouldReceive('consume')
            ->once()
            ->with($state)
            ->andReturnNull();

        $socialOAuthClient = Mockery::mock(SocialOAuthServiceInterface::class);
        $socialOAuthClient->shouldReceive('fetchProfile')
            ->once()
            ->with($provider, $code)
            ->andReturn($profile);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findBySocialConnection')
            ->once()
            ->with($provider, $profile->providerUserId())
            ->andReturnNull();
        $identityRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturnNull();
        $identityRepository->shouldReceive('save')
            ->once()
            ->andReturnNull();

        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $identityFactory->shouldReceive('createFromSocialProfile')
            ->once()
            ->with($profile)
            ->andReturn($newUser);

        $signupSessionRepository = Mockery::mock(SignupSessionRepositoryInterface::class);
        $signupSessionRepository->shouldReceive('find')
            ->once()
            ->with($state)
            ->andReturnNull();
        $signupSessionRepository->shouldNotReceive('delete');

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('login')
            ->once()
            ->with($newUser)
            ->andReturn($newUser);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(
                fn ($event) => $event instanceof IdentityCreated
                    && (string) $event->identityIdentifier === (string) $newUser->identityIdentifier()
                    && (string) $event->email === (string) $email
                    && $event->accountType === AccountType::INDIVIDUAL
                    && $event->name === $profile->name()
            ));

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthServiceInterface::class, $socialOAuthClient);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(SignupSessionRepositoryInterface::class, $signupSessionRepository);
        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(SocialLoginCallbackInterface::class);

        $useCase->process($input, $output);

        $this->assertSame('/auth/callback', $output->redirectUrl());
    }

    /**
     * 異常系: state検証に失敗した場合、例外が伝播されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenStateIsInvalid(): void
    {
        $provider = SocialProvider::GOOGLE;
        $code = new OAuthCode('code');
        $state = new OAuthState('invalid-state', new DateTimeImmutable('+10 minutes'));
        $input = new SocialLoginCallbackInput($provider, $code, $state);
        $output = new SocialLoginCallbackOutput();

        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $oauthStateRepository->shouldReceive('consume')
            ->once()
            ->with($state)
            ->andThrow(new RuntimeException('state mismatch'));

        $socialOAuthClient = Mockery::mock(SocialOAuthServiceInterface::class);
        $socialOAuthClient->shouldNotReceive('fetchProfile');

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $signupSessionRepository = Mockery::mock(SignupSessionRepositoryInterface::class);
        $authService = Mockery::mock(AuthServiceInterface::class);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthServiceInterface::class, $socialOAuthClient);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(SignupSessionRepositoryInterface::class, $signupSessionRepository);
        $this->app->instance(AuthServiceInterface::class, $authService);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(SocialLoginCallbackInterface::class);

        $this->expectException(RuntimeException::class);

        $useCase->process($input, $output);
    }

    /**
     * @param Email $email
     * @param IdentityIdentifier|null $identityIdentifier
     * @param SocialConnection[] $connections
     * @return Identity
     */
    private function createIdentity(Email $email, ?IdentityIdentifier $identityIdentifier = null, array $connections = []): Identity
    {
        $identityIdentifier ??= new IdentityIdentifier(StrTestHelper::generateUuid());
        $username = new UserName('test-user');
        $hashedPassword = HashedPassword::fromPlain(new PlainPassword('PlainPass1!'));
        $language = Language::ENGLISH;

        return new Identity(
            $identityIdentifier,
            $username,
            $email,
            $language,
            null,
            $hashedPassword,
            null,
            $connections,
        );
    }
}
