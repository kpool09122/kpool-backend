<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Affiliation\Query\ListAffiliations;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class ListAffiliationsRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string'],
            'viewerRole' => ['nullable', 'string'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function status(): ?string
    {
        return $this->query('status') !== null ? (string) $this->query('status') : null;
    }

    public function viewerRole(): ?string
    {
        return $this->query('viewerRole') !== null ? (string) $this->query('viewerRole') : null;
    }

    public function perPage(): ?int
    {
        return $this->query('perPage') !== null ? (int) $this->query('perPage') : null;
    }

    public function page(): int
    {
        return $this->query('page') !== null ? (int) $this->query('page') : 1;
    }
}
