<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\GetAccountSnapshotChangeRequestDocument;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Account\Account\Domain\ValueObject\DocumentType;

class GetAccountSnapshotChangeRequestDocumentRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['requestId' => ['required', 'uuid'], 'documentType' => ['required', 'string', Rule::enum(DocumentType::class)], 'source' => ['nullable', 'in:original,proposal']];
    }

    /** @return array<array-key,mixed> */
    public function validationData(): array
    {
        return [...$this->all(), 'requestId' => $this->route('requestId'), 'documentType' => $this->route('documentType')];
    }

    public function requestId(): string
    {
        return (string) $this->route('requestId');
    }

    public function documentSource(): string
    {
        return $this->input('source') === 'original' ? 'original' : 'proposal';
    }

    public function documentType(): string
    {
        return (string) $this->route('documentType');
    }
}
