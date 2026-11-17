<?php

declare(strict_types=1);

namespace Application\Mcp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use SensitiveParameter;
use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;
use Source\Identity\Application\Exception\InvalidInternalApiTokenConfigurationException;
use Source\Identity\Application\Service\InternalApiTokenServiceInterface;

/**
 * Fixed HTTP operations only; never dispatch business Actions or UseCases here.
 * @phpstan-type Account array{accountIdentifier: string, name: string}
 * @phpstan-type Context array{accountIdentifier: ?string, delegationIdentifier: ?string, account: Account|null, originalAccount: Account|null, switchableAccounts: list<array{delegationIdentifier: ?string, accountIdentifier: string, account: Account, isCurrent: bool}>}
 */
readonly class AccountApiAdapter
{
    public function __construct(private InternalApiTokenServiceInterface $internalApiTokenService)
    {
    }

    /**
     * @return array<string, mixed>
     * @throws InvalidAuthorizationTokenException
     * @throws InvalidInternalApiTokenConfigurationException
     */
    public function context(#[SensitiveParameter] string $bearer): array
    {
        $data = $this->request($bearer, 'GET', '/api/v1/identity/auth/me');
        if (Validator::make($data, [
            'accountIdentifier' => 'present|nullable|string',
            'delegationIdentifier' => 'present|nullable|string',
            'account' => 'present|nullable|array|min:2', 'account.accountIdentifier' => 'required_with:account|string', 'account.name' => 'required_with:account|string',
            'originalAccount' => 'present|nullable|array|min:2', 'originalAccount.accountIdentifier' => 'required_with:originalAccount|string', 'originalAccount.name' => 'required_with:originalAccount|string',
            'switchableAccounts' => 'present|array|list',
            'switchableAccounts.*.delegationIdentifier' => 'present|nullable|string',
            'switchableAccounts.*.accountIdentifier' => 'required|string',
            'switchableAccounts.*.account.accountIdentifier' => 'required|string',
            'switchableAccounts.*.account.name' => 'required|string',
            'switchableAccounts.*.isCurrent' => 'required|boolean',
        ])->fails()) {
            throw new ApiFailure(502, 'invalid_api_response');
        }

        /** @var Context $data Validated above at the HTTP boundary. */
        // Project only the documented Account selection contract, not Identity profile/secrets.
        return [
            'accountIdentifier' => $data['accountIdentifier'],
            'delegationIdentifier' => $data['delegationIdentifier'],
            'account' => $this->account($data['account']),
            'originalAccount' => $this->account($data['originalAccount']),
            'switchableAccounts' => array_map(fn (array $candidate): array => [
                'delegationIdentifier' => $candidate['delegationIdentifier'],
                'accountIdentifier' => $candidate['accountIdentifier'],
                'account' => $this->account($candidate['account']),
                'isCurrent' => $candidate['isCurrent'],
            ], $data['switchableAccounts']),
        ];
    }

    /**
     * @return array{switched: true}
     * @throws InvalidAuthorizationTokenException
     * @throws InvalidInternalApiTokenConfigurationException
     */
    public function switchAccount(#[SensitiveParameter] string $bearer, ?string $delegationIdentifier): array
    {
        $this->request($bearer, 'POST', '/api/v1/account/accounts/switch', ['delegationIdentifier' => $delegationIdentifier]);

        return ['switched' => true];
    }

    /** @param array<string, mixed>|null $value
     * @return array<string, mixed>|null
     */
    private function account(?array $value): ?array
    {
        return $value === null ? null : ['accountIdentifier' => $value['accountIdentifier'], 'name' => $value['name']];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws InvalidAuthorizationTokenException
     * @throws InvalidInternalApiTokenConfigurationException
     */
    private function request(#[SensitiveParameter] string $bearer, string $method, string $path, array $data = []): array
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
                ->send($method, rtrim($base, '/') . $path, $method === 'GET' ? [] : ['json' => $data]);
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
