<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\DeleteAccount;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function accountId(): string
    {
        return (string) $this->route('accountId');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
