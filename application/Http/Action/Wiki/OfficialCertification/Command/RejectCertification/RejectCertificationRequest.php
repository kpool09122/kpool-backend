<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\OfficialCertification\Command\RejectCertification;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RejectCertificationRequest extends FormRequest
{
    use ResolvesLanguage;

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
}
