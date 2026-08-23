<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\OfficialCertification\Query\ListMyOfficialCertifications;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;

class ListMyOfficialCertificationsRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(array_column(CertificationStatus::cases(), 'value'))],
        ];
    }

    public function perPage(): ?int
    {
        $perPage = $this->query('perPage');

        return $perPage === null ? null : (int) $perPage;
    }

    public function status(): ?string
    {
        $status = $this->query('status');

        return $status === null ? null : (string) $status;
    }
}
