<?php

declare(strict_types=1);

namespace Tests\Auth\Application\UseCase\Command\SocialLogin\Callback;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use RuntimeException;
use Source\Auth\Application\Service\AccountProvisioningServiceInterface;
use Source\Auth\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallback;
use Source\Auth\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInput;
use Source\Auth\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInterface;
use Source\Auth\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackOutput;
use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\Factory\UserFactoryInterface;
use Source\Auth\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Auth\Domain\Repository\UserRepositoryInterface;
use Source\Auth\Domain\Service\AuthServiceInterface;
use Source\Auth\Domain\Service\SocialOAuthClientInterface;
use Source\Auth\Domain\ValueObject\HashedPassword;
use Source\Auth\Domain\ValueObject\OAuthCode;
use Source\Auth\Domain\ValueObject\OAuthState;
use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\ServiceRole;
use Source\Auth\Domain\ValueObject\SocialConnection;
use Source\Auth\Domain\ValueObject\SocialProfile;
use Source\Auth\Domain\ValueObject\SocialProvider;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\UserIdentifier;
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
        $socialOAuthClient = Mockery::mock(SocialOAuthClientInterface::class);
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userFactory = Mockery::mock(UserFactoryInterface::class);
        $accountProvisioningService = Mockery::mock(AccountProvisioningServiceInterface::class);
        $authService = Mockery::mock(AuthServiceInterface::class);

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthClientInterface::class, $socialOAuthClient);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $this->app->instance(UserFactoryInterface::class, $userFactory);
        $this->app->instance(AccountProvisioningServiceInterface::class, $accountProvisioningService);
        $this->app->instance(AuthServiceInterface::class, $authService);

        $useCase = $this->app->make(SocialLoginCallbackInterface::class);

        $this->assertInstanceOf(SocialLoginCallback::class, $useCase);
    }

    /**
     * 正常系: 既にソーシャル連携済みならそのユーザーでログインすること.
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
        $user = $this->createUser($email, connections: [new SocialConnection($provider, $profile->providerUserId())]);

        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $oauthStateRepository->shouldReceive('consume')
            ->once()
            ->with($state)
            ->andReturnNull();

        $socialOAuthClient = Mockery::mock(SocialOAuthClientInterface::class);
        $socialOAuthClient->shouldReceive('fetchProfile')
            ->once()
            ->with($provider, $code)
            ->andReturn($profile);

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findBySocialConnection')
            ->once()
            ->with($provider, $profile->providerUserId())
            ->andReturn($user);
        $userRepository->shouldNotReceive('findByEmail');
        $userRepository->shouldNotReceive('save');

        $userFactory = Mockery::mock(UserFactoryInterface::class);
        $userFactory->shouldNotReceive('createFromSocialProfile');

        $accountProvisioningService = Mockery::mock(AccountProvisioningServiceInterface::class);
        $accountProvisioningService->shouldReceive('provision')
            ->once()
            ->with($user->userIdentifier())
            ->andReturnNull();

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('login')
            ->once()
            ->with($user)
            ->andReturn($user);

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthClientInterface::class, $socialOAuthClient);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $this->app->instance(UserFactoryInterface::class, $userFactory);
        $this->app->instance(AccountProvisioningServiceInterface::class, $accountProvisioningService);
        $this->app->instance(AuthServiceInterface::class, $authService);

        $useCase = $this->app->make(SocialLoginCallbackInterface::class);

        $useCase->process($input, $output);

        $this->assertSame('/auth/callback', $output->redirectUrl());
    }

    /**
     * 正常系: メール一致のユーザーがいれば連携追加してログインすること.
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
        $existingUser = $this->createUser($email);

        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $oauthStateRepository->shouldReceive('consume')
            ->once()
            ->with($state)
            ->andReturnNull();

        $socialOAuthClient = Mockery::mock(SocialOAuthClientInterface::class);
        $socialOAuthClient->shouldReceive('fetchProfile')
            ->once()
            ->with($provider, $code)
            ->andReturn($profile);

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findBySocialConnection')
            ->once()
            ->with($provider, $profile->providerUserId())
            ->andReturnNull();
        $userRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($existingUser);
        $userRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static function (User $user) use ($provider, $profile): bool {
                return $user->hasSocialConnection(new SocialConnection($provider, $profile->providerUserId()));
            }))
            ->andReturnNull();

        $userFactory = Mockery::mock(UserFactoryInterface::class);
        $userFactory->shouldNotReceive('createFromSocialProfile');

        $accountProvisioningService = Mockery::mock(AccountProvisioningServiceInterface::class);
        $accountProvisioningService->shouldReceive('provision')
            ->once()
            ->with($existingUser->userIdentifier())
            ->andReturnNull();

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('login')
            ->once()
            ->with($existingUser)
            ->andReturn($existingUser);

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthClientInterface::class, $socialOAuthClient);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $this->app->instance(UserFactoryInterface::class, $userFactory);
        $this->app->instance(AccountProvisioningServiceInterface::class, $accountProvisioningService);
        $this->app->instance(AuthServiceInterface::class, $authService);

        $useCase = $this->app->make(SocialLoginCallbackInterface::class);

        $useCase->process($input, $output);

        $this->assertSame('/auth/callback', $output->redirectUrl());
    }

    /**
     * 正常系: 初回ログイン時はユーザー作成と連携追加を行うこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWhenUserNotFoundCreatesNewUser(): void
    {
        $provider = SocialProvider::INSTAGRAM;
        $code = new OAuthCode('code');
        $state = new OAuthState('state-token', new DateTimeImmutable('+10 minutes'));
        $input = new SocialLoginCallbackInput($provider, $code, $state);
        $output = new SocialLoginCallbackOutput();

        $email = new Email('insta-user@example.com');
        $profile = new SocialProfile($provider, 'provider-user-3', $email, 'Insta User');
        $newUser = $this->createUser($email);

        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $oauthStateRepository->shouldReceive('consume')
            ->once()
            ->with($state)
            ->andReturnNull();

        $socialOAuthClient = Mockery::mock(SocialOAuthClientInterface::class);
        $socialOAuthClient->shouldReceive('fetchProfile')
            ->once()
            ->with($provider, $code)
            ->andReturn($profile);

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findBySocialConnection')
            ->once()
            ->with($provider, $profile->providerUserId())
            ->andReturnNull();
        $userRepository->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturnNull();
        $userRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static function (User $user) use ($provider, $profile, $newUser): bool {
                return (string)$user->userIdentifier() === (string)$newUser->userIdentifier()
                    && $user->hasSocialConnection(new SocialConnection($provider, $profile->providerUserId()));
            }))
            ->andReturnNull();

        $userFactory = Mockery::mock(UserFactoryInterface::class);
        $userFactory->shouldReceive('createFromSocialProfile')
            ->once()
            ->with($profile)
            ->andReturn($newUser);

        $accountProvisioningService = Mockery::mock(AccountProvisioningServiceInterface::class);
        $accountProvisioningService->shouldReceive('provision')
            ->once()
            ->with($newUser->userIdentifier())
            ->andReturnNull();

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('login')
            ->once()
            ->with($newUser)
            ->andReturn($newUser);

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthClientInterface::class, $socialOAuthClient);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $this->app->instance(UserFactoryInterface::class, $userFactory);
        $this->app->instance(AccountProvisioningServiceInterface::class, $accountProvisioningService);
        $this->app->instance(AuthServiceInterface::class, $authService);

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

        $socialOAuthClient = Mockery::mock(SocialOAuthClientInterface::class);
        $socialOAuthClient->shouldNotReceive('fetchProfile');

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userFactory = Mockery::mock(UserFactoryInterface::class);
        $accountProvisioningService = Mockery::mock(AccountProvisioningServiceInterface::class);
        $authService = Mockery::mock(AuthServiceInterface::class);

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthClientInterface::class, $socialOAuthClient);
        $this->app->instance(UserRepositoryInterface::class, $userRepository);
        $this->app->instance(UserFactoryInterface::class, $userFactory);
        $this->app->instance(AccountProvisioningServiceInterface::class, $accountProvisioningService);
        $this->app->instance(AuthServiceInterface::class, $authService);

        $useCase = $this->app->make(SocialLoginCallbackInterface::class);

        $this->expectException(RuntimeException::class);

        $useCase->process($input, $output);
    }

    /**
     * @param Email $email
     * @param UserIdentifier|null $userIdentifier
     * @param SocialConnection[] $connections
     * @return User
     */
    private function createUser(Email $email, ?UserIdentifier $userIdentifier = null, array $connections = []): User
    {
        $userIdentifier = $userIdentifier ?? new UserIdentifier(StrTestHelper::generateUlid());
        $username = new UserName('test-user');
        $hashedPassword = HashedPassword::fromPlain(new PlainPassword('PlainPass1!'));
        $language = Language::ENGLISH;
        $serviceRoles = [new ServiceRole('auth', 'user')];

        return new User(
            $userIdentifier,
            $username,
            $email,
            $language,
            null,
            $hashedPassword,
            $serviceRoles,
            null,
            $connections,
        );
    }
}
