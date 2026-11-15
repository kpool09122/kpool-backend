<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\ApproveAuthorization;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Identity\Domain\ValueObject\AuthorizationScope;

class ApproveAuthorizationRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'scopes' => ['required', 'array', 'list', 'min:1', 'max:6'],
            'scopes.*' => ['required', 'string', 'distinct', Rule::enum(AuthorizationScope::class)],
            'targets' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'targets.*' => ['required', 'array:accountIdentifier,delegationIdentifier'],
            'targets.*.accountIdentifier' => ['required', 'uuid', 'distinct'],
            'targets.*.delegationIdentifier' => ['nullable', 'uuid'],
            'initialAccountIdentifier' => ['required', 'uuid'],
        ];
    }

    /** @return list<string> */
    public function scopes(): array
    {
        /** @var list<string> $values */
        $values = $this->input('scopes');

        return $values;
    }

    /** @return list<array<string, mixed>> */
    public function targets(): array
    {
        return RequestValue::objects($this->input('targets'));
    }

    public function initialAccountIdentifier(): string
    {
        return RequestValue::string($this->input('initialAccountIdentifier'));
    }
}
