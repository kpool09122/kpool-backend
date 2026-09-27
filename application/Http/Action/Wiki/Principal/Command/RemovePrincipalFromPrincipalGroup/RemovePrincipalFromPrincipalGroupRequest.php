<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\RemovePrincipalFromPrincipalGroup;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class RemovePrincipalFromPrincipalGroupRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'principalIdentifier' => ['required', 'uuid'],
        ];
    }

    public function principalGroupId(): string
    {
        return (string) $this->route('principalGroupId');
    }

    public function principalIdentifier(): string
    {
        return RequestValue::string($this->input('principalIdentifier'));
    }
}
