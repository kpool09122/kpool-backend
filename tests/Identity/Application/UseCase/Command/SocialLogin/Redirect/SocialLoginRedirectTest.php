<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SocialLogin\Redirect;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use RuntimeException;
use Source\Identity\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirect;
use Source\Identity\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirectInput;
use Source\Identity\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirectInterface;
use Source\Identity\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirectOutput;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Service\OAuthStateGeneratorInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Tests\TestCase;

class SocialLoginRedirectTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $socialOAuthClient = Mockery::mock(SocialOAuthServiceInterface::class);
        $oauthStateGenerator = Mockery::mock(OAuthStateGeneratorInterface::class);
        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $this->app->instance(SocialOAuthServiceInterface::class, $socialOAuthClient);
        $this->app->instance(OAuthStateGeneratorInterface::class, $oauthStateGenerator);
        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);

        $useCase = $this->app->make(SocialLoginRedirectInterface::class);

        $this->assertInstanceOf(SocialLoginRedirect::class, $useCase);
    }

    /**
     * 正常系: state生成・保存・リダイレクトURL生成が順番通りに呼ばれること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $provider = SocialProvider::GOOGLE;
        $input = new SocialLoginRedirectInput($provider);
        $output = new SocialLoginRedirectOutput();

        $state = new OAuthState('state-token', new DateTimeImmutable('+10 minutes'));
        $redirectUrl = 'https://example.com/google/redirect';

        $oauthStateGenerator = Mockery::mock(OAuthStateGeneratorInterface::class);
        $oauthStateGenerator->shouldReceive('generate')
            ->once()
            ->andReturn($state)
            ->ordered('sequence');

        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $oauthStateRepository->shouldReceive('store')
            ->once()
            ->with($state)
            ->andReturnNull()
            ->ordered('sequence');

        $socialOAuthClient = Mockery::mock(SocialOAuthServiceInterface::class);
        $socialOAuthClient->shouldReceive('buildRedirectUrl')
            ->once()
            ->with($provider, $state)
            ->andReturn($redirectUrl)
            ->ordered('sequence');

        $this->app->instance(SocialOAuthServiceInterface::class, $socialOAuthClient);
        $this->app->instance(OAuthStateGeneratorInterface::class, $oauthStateGenerator);
        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);

        $useCase = $this->app->make(SocialLoginRedirectInterface::class);

        $useCase->process($input, $output);

        $this->assertSame($redirectUrl, $output->redirectUrl());
    }

    /**
     * 異常系: state保存失敗時に例外が伝播されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenFailedToStoreState(): void
    {
        $provider = SocialProvider::GOOGLE;
        $input = new SocialLoginRedirectInput($provider);
        $output = new SocialLoginRedirectOutput();

        $state = new OAuthState('state-token', new DateTimeImmutable('+10 minutes'));

        $oauthStateGenerator = Mockery::mock(OAuthStateGeneratorInterface::class);
        $oauthStateGenerator->shouldReceive('generate')
            ->once()
            ->andReturn($state);

        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $oauthStateRepository->shouldReceive('store')
            ->once()
            ->with($state)
            ->andThrow(new RuntimeException('failed to store state'));

        $socialOAuthClient = Mockery::mock(SocialOAuthServiceInterface::class);
        $socialOAuthClient->shouldNotReceive('buildRedirectUrl');

        $this->app->instance(SocialOAuthServiceInterface::class, $socialOAuthClient);
        $this->app->instance(OAuthStateGeneratorInterface::class, $oauthStateGenerator);
        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);

        $useCase = $this->app->make(SocialLoginRedirectInterface::class);

        $this->expectException(RuntimeException::class);

        $useCase->process($input, $output);
    }

    /**
     * 異常系: リダイレクトURL生成失敗時に例外が伝播されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenFailedToBuildRedirectUrl(): void
    {
        $provider = SocialProvider::GOOGLE;
        $input = new SocialLoginRedirectInput($provider);
        $output = new SocialLoginRedirectOutput();

        $state = new OAuthState('state-token', new DateTimeImmutable('+10 minutes'));

        $oauthStateGenerator = Mockery::mock(OAuthStateGeneratorInterface::class);
        $oauthStateGenerator->shouldReceive('generate')
            ->once()
            ->andReturn($state);

        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $oauthStateRepository->shouldReceive('store')
            ->once()
            ->with($state)
            ->andReturnNull();

        $socialOAuthClient = Mockery::mock(SocialOAuthServiceInterface::class);
        $socialOAuthClient->shouldReceive('buildRedirectUrl')
            ->once()
            ->with($provider, $state)
            ->andThrow(new RuntimeException('failed to build redirect url'));

        $this->app->instance(SocialOAuthServiceInterface::class, $socialOAuthClient);
        $this->app->instance(OAuthStateGeneratorInterface::class, $oauthStateGenerator);
        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);

        $useCase = $this->app->make(SocialLoginRedirectInterface::class);

        $this->expectException(RuntimeException::class);

        $useCase->process($input, $output);
    }
}
