<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\SwitchAccount;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class SwitchAccountRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'delegationIdentifier' => ['nullable', 'uuid'],
        ];
    }

    public function delegationIdentifier(): ?string
    {
        $value = $this->input('delegationIdentifier');

        return is_string($value) && $value !== '' ? $value : null;
    }
}
