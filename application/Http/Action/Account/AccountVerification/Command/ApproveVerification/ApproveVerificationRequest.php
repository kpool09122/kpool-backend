<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\AccountVerification\Command\ApproveVerification;

use Illuminate\Foundation\Http\FormRequest;

class ApproveVerificationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reviewerAccountIdentifier' => ['required', 'uuid'],
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

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
