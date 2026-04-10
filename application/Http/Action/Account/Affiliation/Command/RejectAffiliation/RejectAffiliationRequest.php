<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Affiliation\Command\RejectAffiliation;

use Illuminate\Foundation\Http\FormRequest;

class RejectAffiliationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejectorAccountIdentifier' => ['required', 'uuid'],
        ];
    }

    public function affiliationId(): string
    {
        return (string) $this->route('affiliationId');
    }

    public function rejectorAccountIdentifier(): string
    {
        return (string) $this->input('rejectorAccountIdentifier');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
