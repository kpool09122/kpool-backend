<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Command\ReplyContact;

use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class ReplyContactRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'contactIdentifier' => ['required', 'uuid:7'],
            'content' => ['required', 'string'],
        ];
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return array_merge(parent::validationData(), ['contactIdentifier' => $this->route('contactIdentifier')]);
    }

    public function contactIdentifier(): string
    {
        return (string) $this->route('contactIdentifier');
    }

    public function content(): string
    {
        return RequestValue::string($this->input('content'));
    }
}
