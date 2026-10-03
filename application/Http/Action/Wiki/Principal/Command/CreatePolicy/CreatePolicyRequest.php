<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\CreatePolicy;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
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
            'accountId' => ['nullable', 'uuid'],
        ];
    }

    public function name(): string
    {
        return RequestValue::string($this->input('name'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function statements(): array
    {
        return RequestValue::objects($this->input('statements'));
    }

    public function accountIdentifier(): ?string
    {
        $accountId = $this->input('accountId');

        return is_string($accountId) ? $accountId : null;
    }
}
