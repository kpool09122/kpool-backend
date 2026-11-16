<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\ApproveAuthorization;

use Application\Http\Action\Concerns\ResolvesLanguage;
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
