<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\UpdateAccount;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accountName' => ['required', 'string'],
        ];
    }

    public function accountId(): string
    {
        return (string) $this->route('accountId');
    }

    public function accountName(): string
    {
        return (string) $this->input('accountName');
    }
}
