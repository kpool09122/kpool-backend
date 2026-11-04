<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Http\Context\AuthContextCache;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Infrastructure\Service\AuthService;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class AuthSessionLifetimeTest extends TestCase
{
    /** @phpstan-ignore property.uninitialized (Initialized by PHPUnit setUp.) */
    private Store $session;
    /** @phpstan-ignore property.uninitialized (Initialized by PHPUnit setUp.) */
    private AuthService $authService;
    /** @phpstan-ignore property.uninitialized (Initialized by PHPUnit setUp.) */
    private Identity $identity;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-04 12:00:00 UTC'));
        $this->app()['config']->set('session.absolute_lifetime', 43200);
        $this->session = new Store('test', new ArraySessionHandler(10080));
        $this->session->start();
        $request = Request::create('/');
        $request->setLaravelSession($this->session);
        /** @var AuthContextCache $authContextCache */
        $authContextCache = Mockery::mock(AuthContextCache::class);
        /** @var CurrentAccountServiceInterface $currentAccountService */
        $currentAccountService = Mockery::mock(CurrentAccountServiceInterface::class);
        $this->authService = new AuthService($request, $authContextCache, $currentAccountService);
        $this->identity = new Identity(
            new IdentityIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f001'),
            new IdentityName('test-user'),
            new Email('test@example.com'),
            Language::JAPANESE,
            null,
            null,
        );
        Redis::shouldReceive('get')->with('identity_session_generation:' . $this->identity->identityIdentifier())->andReturn(null);
    }

    public function testLoginRecordsAuthenticationTimeAndExpiresAtExactlyThirtyDays(): void
    {
        Auth::shouldReceive('loginUsingId')->once()->with((string) $this->identity->identityIdentifier());
        $this->authService->login($this->identity);
        $this->assertSame(now()->getTimestamp(), $this->session->get('identity_authenticated_at'));

        $this->travel(30 * 24 * 60 * 60 - 1)->seconds();
        $this->assertTrue($this->authService->isCurrentSessionValid($this->identity->identityIdentifier()));
        $this->travel(1)->seconds();
        $this->assertFalse($this->authService->isCurrentSessionValid($this->identity->identityIdentifier()));
    }

    public function testActivityAndSessionIdRegenerationDoNotExtendTheDeadline(): void
    {
        Auth::shouldReceive('loginUsingId')->once();
        $this->authService->login($this->identity);
        $authenticatedAt = $this->session->get('identity_authenticated_at');

        for ($day = 0; $day < 5; $day++) {
            $this->assertTrue($this->authService->isCurrentSessionValid($this->identity->identityIdentifier()));
            $this->session->regenerate();
            $this->assertSame($authenticatedAt, $this->session->get('identity_authenticated_at'));
            $this->travel(6)->days();
        }

        $this->assertFalse($this->authService->isCurrentSessionValid($this->identity->identityIdentifier()));
        $this->assertSame($authenticatedAt, $this->session->get('identity_authenticated_at'));
    }

    public function testNewLoginStartsANewThirtyDayWindow(): void
    {
        Auth::shouldReceive('loginUsingId')->twice();
        $this->authService->login($this->identity);
        $this->travel(30)->days();
        $this->assertFalse($this->authService->isCurrentSessionValid($this->identity->identityIdentifier()));

        $this->authService->login($this->identity);
        $this->assertSame(now()->getTimestamp(), $this->session->get('identity_authenticated_at'));
        $this->assertTrue($this->authService->isCurrentSessionValid($this->identity->identityIdentifier()));
    }

    public function testConfiguredAbsoluteLifetimeIsInMinutes(): void
    {
        $this->app()['config']->set('session.absolute_lifetime', 60);
        Auth::shouldReceive('loginUsingId')->once();
        $this->authService->login($this->identity);
        $this->travel(59)->minutes();
        $this->assertTrue($this->authService->isCurrentSessionValid($this->identity->identityIdentifier()));
        $this->travel(1)->minutes();
        $this->assertFalse($this->authService->isCurrentSessionValid($this->identity->identityIdentifier()));
    }

    #[DataProvider('invalidAuthenticationTimes')]
    public function testMissingMalformedOrFutureLoginTimeIsInvalid(mixed $authenticatedAt): void
    {
        $this->session->put('identity_authenticated_at', $authenticatedAt);
        $this->assertFalse($this->authService->isCurrentSessionValid($this->identity->identityIdentifier()));
        $this->assertSame($authenticatedAt, $this->session->get('identity_authenticated_at'));
    }

    /** @return array<string, array{mixed}> */
    public static function invalidAuthenticationTimes(): array
    {
        return [
            'legacy session' => [null],
            'string' => ['1793793600'],
            'array' => [[]],
            'future' => [4102444800],
        ];
    }
}
