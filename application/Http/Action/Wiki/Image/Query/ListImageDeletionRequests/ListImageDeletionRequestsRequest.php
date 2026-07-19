<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Query\ListImageDeletionRequests;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class ListImageDeletionRequestsRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function perPage(): ?int
    {
        $perPage = $this->query('perPage');

        return $perPage === null ? null : (int) $perPage;
    }
}
