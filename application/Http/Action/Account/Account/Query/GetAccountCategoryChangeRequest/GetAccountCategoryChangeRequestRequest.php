<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\GetAccountCategoryChangeRequest;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class GetAccountCategoryChangeRequestRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'requestId' => ['required', 'uuid'],
        ];
    }

    public function requestId(): string
    {
        return (string) $this->route('requestId');
    }
}
