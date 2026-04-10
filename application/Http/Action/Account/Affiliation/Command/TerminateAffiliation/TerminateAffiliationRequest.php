<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Affiliation\Command\TerminateAffiliation;

use Illuminate\Foundation\Http\FormRequest;

class TerminateAffiliationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'terminatorAccountIdentifier' => ['required', 'uuid'],
        ];
    }

    public function affiliationId(): string
    {
        return (string) $this->route('affiliationId');
    }

    public function terminatorAccountIdentifier(): string
    {
        return (string) $this->input('terminatorAccountIdentifier');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
