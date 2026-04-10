<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\AccountVerification\Command\RequestVerification;

use Illuminate\Foundation\Http\FormRequest;

class RequestVerificationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accountIdentifier' => ['required', 'uuid'],
            'verificationType' => ['required', 'string'],
            'applicantName' => ['required', 'string'],
            'documents' => ['required', 'array', 'min:1'],
            'documents.*.documentType' => ['required', 'string'],
            'documents.*.fileName' => ['required', 'string'],
            'documents.*.fileContents' => ['required', 'string'],
            'documents.*.fileSizeBytes' => ['required', 'integer', 'min:1'],
        ];
    }

    public function accountIdentifier(): string
    {
        return (string) $this->input('accountIdentifier');
    }

    public function verificationType(): string
    {
        return (string) $this->input('verificationType');
    }

    public function applicantName(): string
    {
        return (string) $this->input('applicantName');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function documents(): array
    {
        return (array) $this->input('documents');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
