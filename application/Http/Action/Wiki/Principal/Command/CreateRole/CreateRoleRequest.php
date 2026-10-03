<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\CreateRole;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
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
            'accountId' => ['nullable', 'uuid'],
        ];
    }

    public function name(): string
    {
        return RequestValue::string($this->input('name'));
    }

    /**
     * @return string[]|null
     */
    public function policies(): ?array
    {
        $value = $this->input('policies');

        return $value === null ? null : RequestValue::strings($value);
    }

    public function accountIdentifier(): ?string
    {
        $accountId = $this->input('accountId');

        return is_string($accountId) ? $accountId : null;
    }
}
