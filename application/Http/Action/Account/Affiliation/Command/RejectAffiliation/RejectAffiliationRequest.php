<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Affiliation\Command\RejectAffiliation;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RejectAffiliationRequest extends FormRequest
{
    use ResolvesLanguage;

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
}
