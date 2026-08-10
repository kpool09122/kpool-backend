<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\UploadDocuments;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentsRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:1'],
            'documents.*.documentType' => ['required', 'string'],
            'documents.*.fileContents' => ['required', 'string'],
        ];
    }

    public function accountId(): string
    {
        return (string) $this->route('accountId');
    }

    /** @return array<int, array<string, mixed>> */
    public function documents(): array
    {
        return (array) $this->input('documents');
    }
}
