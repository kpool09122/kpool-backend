<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\GetAccountSnapshotDocument;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Account\Account\Domain\ValueObject\DocumentType;

class GetAccountSnapshotDocumentRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return ['accountIdentifier' => ['required','uuid'], 'snapshotIdentifier' => ['required','uuid'], 'documentType' => ['required',Rule::in(array_column(DocumentType::cases(), 'value'))]];
    }

    /** @return array<array-key,mixed> */
    public function validationData(): array
    {
        return ['accountIdentifier' => $this->route('accountIdentifier'),'snapshotIdentifier' => $this->route('snapshotIdentifier'),'documentType' => $this->route('documentType')];
    }

    public function accountIdentifier(): string
    {
        return (string)$this->route('accountIdentifier');
    }

    public function snapshotIdentifier(): string
    {
        return (string)$this->route('snapshotIdentifier');
    }

    public function documentType(): string
    {
        return (string)$this->route('documentType');
    }
}
