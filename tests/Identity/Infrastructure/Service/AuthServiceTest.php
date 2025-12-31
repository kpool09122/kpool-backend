<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Identity\Infrastructure\Service\AuthService;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\CreateIdentity;
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
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);

        $identity = $this->createIdentityEntity($identityIdentifier);

        $authService = $this->app->make(AuthServiceInterface::class);
        $result = $authService->login($identity);

        $this->assertSame($identity, $result);
        $this->assertTrue(Auth::check());
        $this->assertSame((string) $identityIdentifier, Auth::id());
    }

    /**
     * 正常系: logoutメソッドでユーザーがログアウト状態になること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testLogout(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);

        $identity = $this->createIdentityEntity($identityIdentifier);

        $authService = $this->app->make(AuthServiceInterface::class);
        $authService->login($identity);

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
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);

        $identity = $this->createIdentityEntity($identityIdentifier);

        $authService = $this->app->make(AuthServiceInterface::class);
        $authService->login($identity);

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

    private function createIdentityEntity(IdentityIdentifier $identityIdentifier): Identity
    {
        return new Identity(
            $identityIdentifier,
            new UserName('test-user'),
            new Email('test@example.com'),
            Language::JAPANESE,
            null,
            HashedPassword::fromPlain(new PlainPassword('password123')),
            null,
        );
    }
}
