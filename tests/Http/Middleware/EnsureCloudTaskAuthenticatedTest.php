<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Middleware\EnsureCloudTaskAuthenticated;
use Google\Auth\AccessToken;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EnsureCloudTaskAuthenticatedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'queue.connections.passkey_recovery.handler' => 'https://tasks.example.com',
            'queue.connections.passkey_recovery.service_account_email' => 'tasks@example.iam.gserviceaccount.com',
            'cloud-tasks.uri' => 'internal/queue/passkey-recovery',
        ]);
    }

    public function testMissingConfigurationRejectsRequestBeforeTokenVerification(): void
    {
        config(['queue.connections.passkey_recovery.handler' => '']);
        /** @var AccessToken&\Mockery\MockInterface $tokens */
        $tokens = Mockery::mock(AccessToken::class);
        $tokens->shouldNotReceive('verify');

        $response = (new EnsureCloudTaskAuthenticated($tokens))->handle(Request::create('/'), fn () => response('unexpected'));

        $this->assertSame(503, $response->getStatusCode());
    }

    public function testMissingTokenRejectsRequestBeforeTaskExecution(): void
    {
        /** @var AccessToken&\Mockery\MockInterface $tokens */
        $tokens = Mockery::mock(AccessToken::class);
        $tokens->shouldNotReceive('verify');

        $response = (new EnsureCloudTaskAuthenticated($tokens))->handle(Request::create('/'), fn () => response('unexpected'));

        $this->assertSame(401, $response->getStatusCode());
    }

    /** @param array<string, mixed>|false $claims */
    #[DataProvider('tokenClaims')]
    public function testOnlyVerifiedTokensFromTheConfiguredServiceAccountAreAccepted(array|false $claims, int $status): void
    {
        /** @var AccessToken&\Mockery\MockInterface $tokens */
        $tokens = Mockery::mock(AccessToken::class);
        $tokens->shouldReceive('verify')->once()->with('signed-token', [
            'audience' => 'https://tasks.example.com/internal/queue/passkey-recovery',
            'issuer' => 'https://accounts.google.com',
        ])->andReturn($claims);
        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer signed-token');
        $called = false;

        $response = (new EnsureCloudTaskAuthenticated($tokens))->handle($request, function () use (&$called) {
            $called = true;

            return response()->noContent();
        });

        $this->assertSame($status, $response->getStatusCode());
        $this->assertSame($status === 204, $called);
    }

    /** @return array<string, array{array<string, mixed>|false, int}> */
    public static function tokenClaims(): array
    {
        return [
            'invalid signature, audience or issuer' => [false, 401],
            'wrong service account' => [['email' => 'other@example.iam.gserviceaccount.com', 'email_verified' => true], 401],
            'unverified email' => [['email' => 'tasks@example.iam.gserviceaccount.com', 'email_verified' => false], 401],
            'missing email' => [[], 401],
            'valid service account' => [['email' => 'tasks@example.iam.gserviceaccount.com', 'email_verified' => true], 204],
        ];
    }
}
