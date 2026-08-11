<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\RejectAccountCategoryChangeRequest;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RejectAccountCategoryChangeRequestRequest extends FormRequest
{
    use ResolvesLanguage;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'requestId' => $this->route('requestId'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'requestId' => ['required', 'uuid'],
            'rejectionReasonCode' => ['required', 'string'],
            'rejectionReasonDetail' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function requestId(): string
    {
        return (string) $this->route('requestId');
    }

    public function rejectionReasonCode(): string
    {
        return (string) $this->input('rejectionReasonCode');
    }

    public function rejectionReasonDetail(): ?string
    {
        return $this->input('rejectionReasonDetail');
    }
}
