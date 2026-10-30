<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListSitemapWikis;

use Illuminate\Foundation\Http\FormRequest;

class ListSitemapWikisRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'perPage' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'cursor' => ['nullable', 'string', 'uuid'],
        ];
    }

    public function perPage(): ?int
    {
        $perPage = $this->query('perPage');

        return $perPage === null ? null : (int) $perPage;
    }

    public function cursor(): ?string
    {
        $cursor = $this->query('cursor');

        return $cursor === null ? null : (string) $cursor;
    }
}
