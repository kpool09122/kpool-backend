<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SocialLogin\Callback;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use RuntimeException;
use Source\Identity\Application\Service\AccountProvisioningServiceInterface;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallback;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInput;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInterface;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\Service\SocialOAuthClientInterface;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\OAuthCode;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProfile;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Domain\ValueObject\UserName;
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
        $socialOAuthClient = Mockery::mock(SocialOAuthClientInterface::class);
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $accountProvisioningService = Mockery::mock(AccountProvisioningServiceInterface::class);
        $authService = Mockery::mock(AuthServiceInterface::class);

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthClientInterface::class, $socialOAuthClient);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
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
        $identity = $this->createIdentity($email, connections: [new SocialConnection($provider, $profile->providerUserId())]);

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

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findBySocialConnection')
            ->once()
            ->with($provider, $profile->providerUserId())
            ->andReturn($identity);
        $identityRepository->shouldNotReceive('findByEmail');
        $identityRepository->shouldNotReceive('save');

        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $identityFactory->shouldNotReceive('createFromSocialProfile');

        $accountProvisioningService = Mockery::mock(AccountProvisioningServiceInterface::class);
        $accountProvisioningService->shouldReceive('provision')
            ->once()
            ->with($identity->identityIdentifier())
            ->andReturnNull();

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('login')
            ->once()
            ->with($identity)
            ->andReturn($identity);

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthClientInterface::class, $socialOAuthClient);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
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
        $existingUser = $this->createIdentity($email);

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
            ->with(Mockery::on(static function (Identity $identity) use ($provider, $profile): bool {
                return $identity->hasSocialConnection(new SocialConnection($provider, $profile->providerUserId()));
            }))
            ->andReturnNull();

        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $identityFactory->shouldNotReceive('createFromSocialProfile');

        $accountProvisioningService = Mockery::mock(AccountProvisioningServiceInterface::class);
        $accountProvisioningService->shouldReceive('provision')
            ->once()
            ->with($existingUser->identityIdentifier())
            ->andReturnNull();

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('login')
            ->once()
            ->with($existingUser)
            ->andReturn($existingUser);

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthClientInterface::class, $socialOAuthClient);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
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
        $newUser = $this->createIdentity($email);

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
            ->with(Mockery::on(static function (Identity $identity) use ($provider, $profile, $newUser): bool {
                return (string)$identity->identityIdentifier() === (string)$newUser->identityIdentifier()
                    && $identity->hasSocialConnection(new SocialConnection($provider, $profile->providerUserId()));
            }))
            ->andReturnNull();

        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $identityFactory->shouldReceive('createFromSocialProfile')
            ->once()
            ->with($profile)
            ->andReturn($newUser);

        $accountProvisioningService = Mockery::mock(AccountProvisioningServiceInterface::class);
        $accountProvisioningService->shouldReceive('provision')
            ->once()
            ->with($newUser->identityIdentifier())
            ->andReturnNull();

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('login')
            ->once()
            ->with($newUser)
            ->andReturn($newUser);

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthClientInterface::class, $socialOAuthClient);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
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

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $accountProvisioningService = Mockery::mock(AccountProvisioningServiceInterface::class);
        $authService = Mockery::mock(AuthServiceInterface::class);

        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);
        $this->app->instance(SocialOAuthClientInterface::class, $socialOAuthClient);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(AccountProvisioningServiceInterface::class, $accountProvisioningService);
        $this->app->instance(AuthServiceInterface::class, $authService);

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
        $identityIdentifier = $identityIdentifier ?? new IdentityIdentifier(StrTestHelper::generateUuid());
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
