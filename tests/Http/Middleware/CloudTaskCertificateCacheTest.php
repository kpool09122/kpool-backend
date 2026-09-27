<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use Application\Http\Middleware\EnsureCloudTaskAuthenticated;
use Firebase\JWT\JWT;
use Google\Auth\AccessToken;
use Google\Auth\HttpHandler\HttpClientCache;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CloudTaskCertificateCacheTest extends TestCase
{
    #[DataProvider('cacheLifetimes')]
    public function testCertificatesAreSharedAcrossApplicationsUntilTheyExpire(int $maxAge, int $remainingResponses): void
    {
        $directory = sys_get_temp_dir() . '/cloud-task-certs-' . bin2hex(random_bytes(8));
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($key);
        $details = openssl_pkey_get_details($key);
        self::assertNotFalse($details);
        self::assertIsArray($details['rsa']);
        self::assertIsString($details['rsa']['n']);
        self::assertIsString($details['rsa']['e']);
        $certificates = json_encode(['keys' => [[
            'kty' => 'RSA',
            'alg' => 'RS256',
            'kid' => 'test-key',
            'n' => JWT::urlsafeB64Encode($details['rsa']['n']),
            'e' => JWT::urlsafeB64Encode($details['rsa']['e']),
        ]]], JSON_THROW_ON_ERROR);
        $handler = new MockHandler([
            new Response(200, ['Cache-Control' => 'max-age=' . $maxAge], $certificates),
            new Response(200, ['Cache-Control' => 'max-age=' . $maxAge], $certificates),
        ]);
        HttpClientCache::setHttpClient(new Client(['handler' => $handler]));
        $token = JWT::encode([
            'aud' => 'https://tasks.example.com/internal/queue/default',
            'iss' => 'https://accounts.google.com',
            'email' => 'tasks@example.iam.gserviceaccount.com',
            'email_verified' => true,
            'exp' => time() + 3600,
        ], $key, 'RS256', 'test-key');

        try {
            $this->configureCache($directory);
            $firstTokens = $this->app()->make(AccessToken::class);
            self::assertSame($firstTokens, $this->app()->make(AccessToken::class));
            $this->assertTaskAccepted($token);

            $this->refreshApplication();
            $this->configureCache($directory);
            self::assertNotSame($firstTokens, $this->app()->make(AccessToken::class));
            $this->assertTaskAccepted($token);

            self::assertSame($remainingResponses, $handler->count());
        } finally {
            HttpClientCache::setHttpClient();
            (new Filesystem())->deleteDirectory($directory);
        }
    }

    /** @return array<string, array{int, int}> */
    public static function cacheLifetimes(): array
    {
        return [
            'reuse certificates before expiry' => [3600, 1],
            'fetch certificates again after expiry' => [0, 0],
        ];
    }

    private function configureCache(string $directory): void
    {
        config([
            'cache.default' => 'file',
            'cache.stores.file' => ['driver' => 'file', 'path' => $directory],
            'queue.connections.cloudtasks.handler' => 'https://tasks.example.com',
            'queue.connections.cloudtasks.service_account_email' => 'tasks@example.iam.gserviceaccount.com',
            'cloud-tasks.uri' => 'internal/queue/default',
        ]);
    }

    private function assertTaskAccepted(string $token): void
    {
        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer ' . $token);
        $response = $this->app()->make(EnsureCloudTaskAuthenticated::class)->handle(
            $request,
            fn () => response()->noContent(),
        );

        self::assertSame(204, $response->getStatusCode());
    }
}
