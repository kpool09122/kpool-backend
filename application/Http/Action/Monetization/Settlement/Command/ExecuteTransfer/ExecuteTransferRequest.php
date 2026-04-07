<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Settlement\Command\ExecuteTransfer;

use Illuminate\Foundation\Http\FormRequest;

class ExecuteTransferRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'transferId' => ['required', 'uuid'],
        ];
    }

    public function transferId(): string
    {
        return (string) $this->input('transferId');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
