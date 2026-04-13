<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\CreatePrincipal;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class CreatePrincipalRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'identityIdentifier' => ['required', 'uuid'],
            'accountIdentifier' => ['required', 'uuid'],
        ];
    }

    public function identityIdentifier(): string
    {
        return (string) $this->input('identityIdentifier');
    }

    public function accountIdentifier(): string
    {
        return (string) $this->input('accountIdentifier');
    }
}
