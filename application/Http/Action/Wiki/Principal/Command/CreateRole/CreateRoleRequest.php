<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\CreateRole;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class CreateRoleRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'policies' => ['nullable', 'array'],
            'policies.*' => ['uuid'],
            'isSystemRole' => ['required', 'boolean'],
        ];
    }

    public function name(): string
    {
        return (string) $this->input('name');
    }

    /**
     * @return string[]|null
     */
    public function policies(): ?array
    {
        return $this->input('policies');
    }

    public function isSystemRole(): bool
    {
        return (bool) $this->input('isSystemRole');
    }
}
