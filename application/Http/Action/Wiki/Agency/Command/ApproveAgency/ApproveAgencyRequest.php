<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Agency\Command\ApproveAgency;

use Illuminate\Foundation\Http\FormRequest;

class ApproveAgencyRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'agencyId' => ['required', 'uuid'],
            'principalId' => ['required', 'uuid'],
        ];
    }

    public function agencyId(): string
    {
        return (string)$this->input('agencyId');
    }

    public function principalId(): string
    {
        return (string)$this->input('principalId');
    }

    public function language(): string
    {
        return (string)($this->input('language') ?? 'en');
    }
}
