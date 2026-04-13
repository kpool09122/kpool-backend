<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\AccountVerification\Command\RejectVerification;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RejectVerificationRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reviewerAccountIdentifier' => ['required', 'uuid'],
            'rejectionReasonCode' => ['required', 'string'],
            'rejectionReasonDetail' => ['nullable', 'string'],
        ];
    }

    public function verificationId(): string
    {
        return (string) $this->route('verificationId');
    }

    public function reviewerAccountIdentifier(): string
    {
        return (string) $this->input('reviewerAccountIdentifier');
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
