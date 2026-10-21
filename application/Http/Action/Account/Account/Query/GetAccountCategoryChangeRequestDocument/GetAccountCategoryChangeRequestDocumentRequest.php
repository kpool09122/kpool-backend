<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\GetAccountCategoryChangeRequestDocument;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class GetAccountCategoryChangeRequestDocumentRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    public function requestId(): string
    {
        return (string) $this->route('requestId');
    }

    public function documentType(): string
    {
        return (string) $this->route('documentType');
    }
}
