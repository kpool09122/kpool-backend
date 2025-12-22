<?php

declare(strict_types=1);

namespace Tests\Auth\Infrastructure\Service;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Group;
use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\Service\AuthServiceInterface;
use Source\Auth\Domain\ValueObject\HashedPassword;
use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Auth\Infrastructure\Service\AuthService;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Tests\Helper\CreateUser;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class AuthServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // テスト用にセッションドライバーをarrayに設定
        $this->app['config']->set('session.driver', 'array');

        // セッションマネージャーを再作成してドライバー設定を反映
        $this->app->forgetInstance('session');
        $this->app->forgetInstance('session.store');

        // セッションを開始し、Requestに設定する
        $this->app['session']->start();
        $this->app['request']->setLaravelSession($this->app['session.store']);
    }

    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $authService = $this->app->make(AuthServiceInterface::class);
        $this->assertInstanceOf(AuthService::class, $authService);
    }

    /**
     * 正常系: loginメソッドでユーザーがログイン状態になること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testLogin(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        CreateUser::create($userIdentifier);

        $user = $this->createUserEntity($userIdentifier);

        $authService = $this->app->make(AuthServiceInterface::class);
        $result = $authService->login($user);

        $this->assertSame($user, $result);
        $this->assertTrue(Auth::check());
        $this->assertSame((string) $userIdentifier, Auth::id());
    }

    /**
     * 正常系: logoutメソッドでユーザーがログアウト状態になること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testLogout(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        CreateUser::create($userIdentifier);

        $user = $this->createUserEntity($userIdentifier);

        $authService = $this->app->make(AuthServiceInterface::class);
        $authService->login($user);

        $this->assertTrue(Auth::check());

        $authService->logout();

        $this->assertFalse(Auth::check());
    }

    /**
     * 正常系: isLoggedInメソッドでログイン済みの場合trueが返ること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testIsLoggedInWhenAuthenticated(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        CreateUser::create($userIdentifier);

        $user = $this->createUserEntity($userIdentifier);

        $authService = $this->app->make(AuthServiceInterface::class);
        $authService->login($user);

        $this->assertTrue($authService->isLoggedIn());
    }

    /**
     * 正常系: isLoggedInメソッドで未ログインの場合falseが返ること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testIsLoggedInWhenNotAuthenticated(): void
    {
        $authService = $this->app->make(AuthServiceInterface::class);

        $this->assertFalse($authService->isLoggedIn());
    }

    private function createUserEntity(UserIdentifier $userIdentifier): User
    {
        return new User(
            $userIdentifier,
            new UserName('test-user'),
            new Email('test@example.com'),
            Language::JAPANESE,
            null,
            HashedPassword::fromPlain(new PlainPassword('password123')),
            [],
            null,
        );
    }
}
