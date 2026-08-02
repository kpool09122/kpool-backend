<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\ListMyAccountDocuments;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class ListMyAccountDocumentsRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
