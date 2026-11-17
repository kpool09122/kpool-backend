<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\UpdateAuthorizationScopeSettings;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Identity\Domain\ValueObject\AuthorizationScope;

class UpdateAuthorizationScopeSettingsRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'scopes' => ['present', 'array', 'list', 'max:' . count(AuthorizationScope::cases())],
            'scopes.*' => ['required', 'string', 'distinct:strict', Rule::enum(AuthorizationScope::class)],
        ];
    }

    /** @return list<string> */
    public function scopes(): array
    {
        /** @var list<string> $values */
        $values = $this->input('scopes');

        return $values;
    }
}
