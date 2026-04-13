<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Affiliation\Command\ApproveAffiliation;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class ApproveAffiliationRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'approverAccountIdentifier' => ['required', 'uuid'],
        ];
    }

    public function affiliationId(): string
    {
        return (string) $this->route('affiliationId');
    }

    public function approverAccountIdentifier(): string
    {
        return (string) $this->input('approverAccountIdentifier');
    }
}
