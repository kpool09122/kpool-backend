<?php

declare(strict_types=1);

namespace Tests\Auth\Application\UseCase\Command\Logout;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Auth\Application\UseCase\Command\Logout\Logout;
use Source\Auth\Application\UseCase\Command\Logout\LogoutInput;
use Source\Auth\Application\UseCase\Command\Logout\LogoutInterface;
use Source\Auth\Domain\Service\AuthServiceInterface;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $authService = Mockery::mock(AuthServiceInterface::class);
        $this->app->instance(AuthServiceInterface::class, $authService);
        $useCase = $this->app->make(LogoutInterface::class);
        $this->assertInstanceOf(Logout::class, $useCase);
    }

    /**
     * 正常系: ログインしている場合、ログアウトできること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWhenLoggedIn(): void
    {
        $input = new LogoutInput();

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(true);
        $authService->shouldReceive('logout')
            ->once();

        $this->app->instance(AuthServiceInterface::class, $authService);
        $useCase = $this->app->make(LogoutInterface::class);

        $useCase->process($input);
    }

    /**
     * 正常系: ログインしていない場合、何も起こらないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWhenNotLoggedIn(): void
    {
        $input = new LogoutInput();

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('isLoggedIn')
            ->once()
            ->andReturn(false);
        $authService->shouldNotReceive('logout');

        $this->app->instance(AuthServiceInterface::class, $authService);
        $useCase = $this->app->make(LogoutInterface::class);

        $useCase->process($input);
    }
}
