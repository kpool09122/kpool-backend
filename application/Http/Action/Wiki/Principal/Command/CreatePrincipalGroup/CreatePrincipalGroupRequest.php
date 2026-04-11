<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\CreatePrincipalGroup;

use Illuminate\Foundation\Http\FormRequest;

class CreatePrincipalGroupRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accountIdentifier' => ['required', 'uuid'],
            'name' => ['required', 'string'],
        ];
    }

    public function accountIdentifier(): string
    {
        return (string) $this->input('accountIdentifier');
    }

    public function name(): string
    {
        return (string) $this->input('name');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
