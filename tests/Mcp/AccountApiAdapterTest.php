<?php

declare(strict_types=1);

namespace Tests\Mcp;

use Application\Mcp\AccountApiAdapter;
use Application\Mcp\ApiFailure;
use DateTimeImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Identity\Application\Service\InternalApiToken;
use Source\Identity\Application\Service\InternalApiTokenServiceInterface;
use Tests\TestCase;

class AccountApiAdapterTest extends TestCase
{
    /** @return list<array{array<string, mixed>}> */
    public static function invalidContexts(): array
    {
        return [[['account' => []]], [['originalAccount' => []]], [['switchableAccounts' => 'wrong']]];
    }

    /** @param array<string, mixed> $override */
    #[DataProvider('invalidContexts')]
    public function testMalformedContextIsRejectedRatherThanInventingValues(array $override): void
    {
        config()->set('mcp_server.api_base_url', 'https://api.example.test');
        /** @var InternalApiTokenServiceInterface&MockInterface $internalApiTokenService */
        $internalApiTokenService = Mockery::mock(InternalApiTokenServiceInterface::class);
        $internalApiTokenService->shouldReceive('issue')->once()->andReturn(new InternalApiToken('internal-secret', new DateTimeImmutable('+1 minute')));
        Http::fake(['*' => Http::response($override + ['accountIdentifier' => null, 'delegationIdentifier' => null, 'account' => null, 'originalAccount' => null, 'switchableAccounts' => []])]);
        $this->expectException(ApiFailure::class);
        $this->expectExceptionMessage('invalid_api_response');
        (new AccountApiAdapter($internalApiTokenService))->context('mcp-token');
    }

    public function testCandidateProjectionPreservesCurrentSelectionAndStripsUnexpectedNestedData(): void
    {
        config()->set('mcp_server.api_base_url', 'https://api.example.test');
        /** @var InternalApiTokenServiceInterface&MockInterface $internalApiTokenService */
        $internalApiTokenService = Mockery::mock(InternalApiTokenServiceInterface::class);
        $internalApiTokenService->shouldReceive('issue')->once()->andReturn(new InternalApiToken('internal-secret', new DateTimeImmutable('+1 minute')));
        Http::fake(['*' => Http::response(['accountIdentifier' => null, 'delegationIdentifier' => null, 'account' => null, 'originalAccount' => null,
            'switchableAccounts' => [['delegationIdentifier' => 'delegation', 'accountIdentifier' => 'target', 'account' => ['accountIdentifier' => 'target', 'name' => 'Target', 'token' => 'secret'], 'isCurrent' => false, 'token' => 'secret']]])]);
        self::assertSame(['accountIdentifier' => null, 'delegationIdentifier' => null, 'account' => null, 'originalAccount' => null,
            'switchableAccounts' => [['delegationIdentifier' => 'delegation', 'accountIdentifier' => 'target', 'account' => ['accountIdentifier' => 'target', 'name' => 'Target'], 'isCurrent' => false]]], (new AccountApiAdapter($internalApiTokenService))->context('mcp-token'));
    }

    public function testContextUsesFixedApiWithInternalBearerAndFiltersUnexpectedFields(): void
    {
        config()->set('mcp_server.api_base_url', 'https://api.example.test');
        /** @var InternalApiTokenServiceInterface&MockInterface $internalApiTokenService */
        $internalApiTokenService = Mockery::mock(InternalApiTokenServiceInterface::class);
        $internalApiTokenService->shouldReceive('issue')->once()->with('mcp-token')->andReturn(new InternalApiToken('internal-secret', new DateTimeImmutable('+1 minute')));
        Http::preventStrayRequests();
        Http::fake(['https://api.example.test/api/v1/identity/auth/me' => Http::response([
            'accountIdentifier' => null, 'delegationIdentifier' => null, 'account' => null,
            'originalAccount' => ['accountIdentifier' => 'original', 'name' => 'Original'],
            'switchableAccounts' => [], 'access_token' => 'never-return', 'email' => 'private@example.test',
        ])]);
        $result = (new AccountApiAdapter($internalApiTokenService))->context('mcp-token');
        self::assertSame(null, $result['accountIdentifier']);
        self::assertSame([], $result['switchableAccounts']);
        self::assertArrayNotHasKey('access_token', $result);
        self::assertArrayNotHasKey('email', $result);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET' && $request->hasHeader('Authorization', 'Bearer internal-secret'));
    }
}
