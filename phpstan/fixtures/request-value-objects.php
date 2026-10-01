<?php

namespace Application\Http\Action\RuleFixture;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Shared\Domain\ValueObject\AccountIdentifier as AccountId;
use Source\Account\Shared\Domain\ValueObject\AccountType;

class ExampleRequest extends FormRequest
{
    private ?AccountId $account;
    /** @var list<AccountId> */
    private array $accounts;
    public function account(AccountId $account): ?AccountId
    {
        return $account;
    }
    /** @return list<AccountId> */
    public function accounts(): array
    {
        return [new AccountId('123e4567-e89b-72d3-a456-426614174001')];
    }
    public function inferred(): mixed
    {
        return AccountType::INDIVIDUAL;
    }
    public function primitive(): string
    {
        return 'individual';
    }
    public function rules(): array
    {
        return ['accountType' => [Rule::enum(AccountType::class)]];
    }
}

class ExampleAction
{
    public function account(): AccountId
    {
        return new AccountId('123e4567-e89b-72d3-a456-426614174001');
    }
}
