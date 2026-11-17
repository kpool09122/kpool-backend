<?php

declare(strict_types=1);

namespace Application\Mcp;

use Illuminate\Support\Facades\Validator;

/**
 * @phpstan-type Account array{accountIdentifier: string, name: string}
 * @phpstan-type Context array{accountIdentifier: ?string, delegationIdentifier: ?string, account: Account|null, originalAccount: Account|null, switchableAccounts: list<array{delegationIdentifier: ?string, accountIdentifier: string, account: Account, isCurrent: bool}>}
 */
readonly class AccountResultProjector
{
    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function context(array $data): array
    {
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

    /** @param array<string, mixed>|null $value
     * @return array<string, mixed>|null
     */
    private function account(?array $value): ?array
    {
        return $value === null ? null : ['accountIdentifier' => $value['accountIdentifier'], 'name' => $value['name']];
    }
}
