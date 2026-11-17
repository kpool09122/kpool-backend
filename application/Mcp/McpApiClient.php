<?php

declare(strict_types=1);

namespace Application\Mcp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use SensitiveParameter;
use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;
use Source\Identity\Application\Exception\InvalidInternalApiTokenConfigurationException;
use Source\Identity\Application\Service\InternalApiTokenServiceInterface;

readonly class McpApiClient
{
    public function __construct(private InternalApiTokenServiceInterface $internalApiTokenService)
    {
    }

    /**
     * @param array<string, mixed>|null $data
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     * @throws InvalidAuthorizationTokenException
     * @throws InvalidInternalApiTokenConfigurationException
     */
    public function request(#[SensitiveParameter] string $bearer, string $method, string $path, ?array $data = null, array $query = []): array
    {
        $base = config()->string('mcp_server.api_base_url');
        if (! filter_var($base, FILTER_VALIDATE_URL) || parse_url($base, PHP_URL_USER) !== null
            || parse_url($base, PHP_URL_QUERY) !== null || parse_url($base, PHP_URL_FRAGMENT) !== null
            || (parse_url($base, PHP_URL_SCHEME) !== 'https' && ! app()->environment(['local', 'testing']))) {
            throw new ApiFailure(503, 'invalid_api_configuration');
        }
        $token = $this->internalApiTokenService->issue($bearer);

        try {
            $response = Http::acceptJson()->asJson()->withToken($token->reveal())
                ->connectTimeout(3)->timeout(15)->withoutRedirecting()
                ->send($method, rtrim($base, '/') . $path, ['query' => $query] + ($data === null ? [] : ['json' => (object) $data]));
        } catch (ConnectionException) {
            // Do not retain the transport exception: it can contain the Bearer credential.
            throw new ApiFailure(502, 'api_unavailable');
        }
        if (! $response->successful()) {
            $status = $response->status();
            $code = match ($status) {
                401 => 'authentication_required',
                403 => $response->json('code') === 'insufficient_scope' ? 'insufficient_scope' : 'forbidden',
                404 => 'not_found', 409 => 'conflict', 422 => 'validation_failed', 429 => 'rate_limited',
                default => 'api_unavailable',
            };
            $retryAfter = $response->header('Retry-After');

            throw new ApiFailure(
                in_array($status, [401, 403, 404, 409, 422, 429], true) ? $status : 502,
                $code,
                $status === 429 && ctype_digit($retryAfter) ? min(86400, (int) $retryAfter) : null
            );
        }
        $body = $response->json();
        if (! is_array($body) || ($body !== [] && array_is_list($body))) {
            throw new ApiFailure(502, 'invalid_api_response');
        }

        /** @var array<string, mixed> $body */
        return $body;
    }
}
