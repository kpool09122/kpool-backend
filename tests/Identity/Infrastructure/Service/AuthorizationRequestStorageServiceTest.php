<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Application\Exception\AuthorizationRequestUnavailableException;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationRequestStorageServiceInterface;
use Source\Identity\Application\Service\PendingAuthorization;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class AuthorizationRequestStorageServiceTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function invalidAccesses(): array
    {
        return [
            'different browser' => ['browser'],
            'different identity' => ['identity'],
            'expired' => ['expired'],
            'unknown' => ['unknown'],
            'legacy unbound request' => ['unbound'],
            'replaced request' => ['replaced'],
            'older request in a legacy session' => ['legacy'],
        ];
    }

    #[DataProvider('invalidAccesses')]
    public function testRejectsForeignExpiredOrModifiedRequest(string $case): void
    {
        $request = Request::create('/');
        $session = new Store('oauth-test', new ArraySessionHandler(30));
        $session->start();
        $request->setLaravelSession($session);
        app()->instance('request', $request);
        $service = app(AuthorizationRequestStorageServiceInterface::class);
        $parameters = new AuthorizationCodeParameters(new OAuthClientIdentifier((string) Str::uuid7()), 'https://client.test/cb', [AuthorizationScope::WikiRead], 'https://mcp.test', str_repeat('a', 43), 'state');
        $identity = new IdentityIdentifier((string) Str::uuid7());
        $pending = $service->store($parameters, $identity);
        if ($case === 'browser') {
            $otherRequest = Request::create('/');
            $otherSession = new Store('other', new ArraySessionHandler(30));
            $otherSession->start();
            $otherRequest->setLaravelSession($otherSession);
            app()->instance('request', $otherRequest);
            $this->assertSame($service, app(AuthorizationRequestStorageServiceInterface::class));
        } elseif ($case === 'expired') {
            $session->put('oauth_authorization_requests', [$pending->identifier => new PendingAuthorization($pending->identifier, $parameters, new DateTimeImmutable('-1 second'), $identity)]);
        } elseif ($case === 'unbound') {
            $session->put('oauth_authorization_requests', [$pending->identifier => new PendingAuthorization($pending->identifier, $parameters, $pending->expiresAt, null)]);
        } elseif ($case === 'replaced' || $case === 'legacy') {
            $latest = $service->store($parameters, $identity);
            if ($case === 'legacy') {
                $session->put('oauth_authorization_requests', [$pending->identifier => $pending, $latest->identifier => $latest]);
            } else {
                $this->assertSame([$latest->identifier => $latest], $session->get('oauth_authorization_requests'));
            }
            $this->assertSame($latest, $service->requireValid($latest->identifier, $identity));
        }
        $this->expectException(AuthorizationRequestUnavailableException::class);
        $service->requireValid(
            $case === 'unknown' ? 'modified-id' : $pending->identifier,
            $case === 'identity' ? new IdentityIdentifier((string) Str::uuid7()) : $identity,
        );
    }

    public function testRequestBelongsToCreatingIdentityAndCompletesOnce(): void
    {
        $request = Request::create('/');
        $session = new Store('oauth-test', new ArraySessionHandler(30));
        $session->start();
        $request->setLaravelSession($session);
        app()->instance('request', $request);
        $service = app(AuthorizationRequestStorageServiceInterface::class);
        $parameters = new AuthorizationCodeParameters(new OAuthClientIdentifier((string) Str::uuid7()), 'https://client.test/cb', [AuthorizationScope::WikiRead], 'https://mcp.test', str_repeat('a', 43), 'original-state');
        $identity = new IdentityIdentifier((string) Str::uuid7());
        $pending = $service->store($parameters, $identity);
        $this->assertSame($identity, $pending->identityIdentifier);
        $this->assertEquals($parameters, $service->requireValid($pending->identifier, $identity)->parameters);
        $this->assertEquals($identity, $service->requireValid($pending->identifier, $identity)->identityIdentifier);
        $service->complete($pending->identifier, $identity);
        $this->expectException(AuthorizationRequestUnavailableException::class);
        $service->requireValid($pending->identifier, $identity);
    }

    /** @return array<string, array{string}> */
    public static function transactionOutcomes(): array
    {
        return ['commit' => ['commit'], 'rollback' => ['rollback'], 'commit after replacement' => ['replaced']];
    }

    #[Group('useDb')]
    #[DataProvider('transactionOutcomes')]
    public function testCompletesOnlyWhenTransactionCommitsAndRequestIsStillLatest(string $outcome): void
    {
        $request = Request::create('/');
        $session = new Store('oauth-test', new ArraySessionHandler(30));
        $session->start();
        $request->setLaravelSession($session);
        app()->instance('request', $request);
        $service = app(AuthorizationRequestStorageServiceInterface::class);
        $parameters = new AuthorizationCodeParameters(new OAuthClientIdentifier((string) Str::uuid7()), 'https://client.test/cb', [AuthorizationScope::WikiRead], 'https://mcp.test', str_repeat('a', 43), 'state');
        $identity = new IdentityIdentifier((string) Str::uuid7());
        $pending = $service->store($parameters, $identity);

        DB::commit();

        try {
            DB::beginTransaction();
            $service->complete($pending->identifier, $identity);
            $this->assertFalse($service->requireValid($pending->identifier, $identity)->completed);

            if ($outcome === 'replaced') {
                $latest = $service->store($parameters, $identity);
                DB::commit();
                $this->assertSame($latest, $service->requireValid($latest->identifier, $identity));

                return;
            }

            if ($outcome === 'commit') {
                $otherRequest = Request::create('/');
                $otherSession = new Store('other', new ArraySessionHandler(30));
                $otherSession->start();
                $otherRequest->setLaravelSession($otherSession);
                app()->instance('request', $otherRequest);
                DB::commit();
                $this->assertFalse($otherSession->has('oauth_authorization_requests'));

                app()->instance('request', $request);
                $this->expectException(AuthorizationRequestUnavailableException::class);
                $service->requireValid($pending->identifier, $identity);
            } else {
                DB::rollBack();
                $this->assertFalse($service->requireValid($pending->identifier, $identity)->completed);
            }
        } finally {
            DB::beginTransaction();
        }
    }
}
