<?php

declare(strict_types=1);

namespace Tests\Auth\Application\UseCase\Command\SocialLogin\Redirect;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use RuntimeException;
use Source\Auth\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirect;
use Source\Auth\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirectInput;
use Source\Auth\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirectInterface;
use Source\Auth\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirectOutput;
use Source\Auth\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Auth\Domain\Service\OAuthStateGeneratorInterface;
use Source\Auth\Domain\Service\SocialOAuthClientInterface;
use Source\Auth\Domain\ValueObject\OAuthState;
use Source\Auth\Domain\ValueObject\SocialProvider;
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
        $socialOAuthClient = Mockery::mock(SocialOAuthClientInterface::class);
        $oauthStateGenerator = Mockery::mock(OAuthStateGeneratorInterface::class);
        $oauthStateRepository = Mockery::mock(OAuthStateRepositoryInterface::class);
        $this->app->instance(SocialOAuthClientInterface::class, $socialOAuthClient);
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

        $socialOAuthClient = Mockery::mock(SocialOAuthClientInterface::class);
        $socialOAuthClient->shouldReceive('buildRedirectUrl')
            ->once()
            ->with($provider, $state)
            ->andReturn($redirectUrl)
            ->ordered('sequence');

        $this->app->instance(SocialOAuthClientInterface::class, $socialOAuthClient);
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

        $socialOAuthClient = Mockery::mock(SocialOAuthClientInterface::class);
        $socialOAuthClient->shouldNotReceive('buildRedirectUrl');

        $this->app->instance(SocialOAuthClientInterface::class, $socialOAuthClient);
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

        $socialOAuthClient = Mockery::mock(SocialOAuthClientInterface::class);
        $socialOAuthClient->shouldReceive('buildRedirectUrl')
            ->once()
            ->with($provider, $state)
            ->andThrow(new RuntimeException('failed to build redirect url'));

        $this->app->instance(SocialOAuthClientInterface::class, $socialOAuthClient);
        $this->app->instance(OAuthStateGeneratorInterface::class, $oauthStateGenerator);
        $this->app->instance(OAuthStateRepositoryInterface::class, $oauthStateRepository);

        $useCase = $this->app->make(SocialLoginRedirectInterface::class);

        $this->expectException(RuntimeException::class);

        $useCase->process($input, $output);
    }
}
