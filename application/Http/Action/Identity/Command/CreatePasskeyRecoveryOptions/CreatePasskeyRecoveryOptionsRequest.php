<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\CreatePasskeyRecoveryOptions;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class CreatePasskeyRecoveryOptionsRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'recoveryKey' => ['required', 'uuid'],
        ];
    }

    public function recoveryKey(): string
    {
        return RequestValue::string($this->input('recoveryKey'));
    }
}
