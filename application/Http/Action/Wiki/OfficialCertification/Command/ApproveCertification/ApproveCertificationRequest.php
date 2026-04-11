<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\OfficialCertification\Command\ApproveCertification;

use Illuminate\Foundation\Http\FormRequest;

class ApproveCertificationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function certificationId(): string
    {
        return (string) $this->route('certificationId');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
