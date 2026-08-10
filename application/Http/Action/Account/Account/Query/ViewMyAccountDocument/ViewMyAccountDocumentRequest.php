<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\ViewMyAccountDocument;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class ViewMyAccountDocumentRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    public function documentType(): string
    {
        return (string) $this->route('documentType');
    }
}
