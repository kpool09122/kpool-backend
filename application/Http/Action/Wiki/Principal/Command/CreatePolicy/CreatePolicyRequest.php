<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\CreatePolicy;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class CreatePolicyRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'statements' => ['required', 'array'],
            'isSystemPolicy' => ['required', 'boolean'],
        ];
    }

    public function name(): string
    {
        return (string) $this->input('name');
    }

    /**
     * @return array<int, mixed>
     */
    public function statements(): array
    {
        return (array) $this->input('statements');
    }

    public function isSystemPolicy(): bool
    {
        return (bool) $this->input('isSystemPolicy');
    }
}
