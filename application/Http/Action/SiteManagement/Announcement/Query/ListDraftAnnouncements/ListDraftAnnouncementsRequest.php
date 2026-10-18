<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Announcement\Query\ListDraftAnnouncements;

use Illuminate\Foundation\Http\FormRequest;

class ListDraftAnnouncementsRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            'sort' => ['nullable', 'string', 'in:published_date,created_at'],
            'searchWords' => ['nullable', 'string'],
        ];
    }

    public function perPage(): ?int
    {
        $perPage = $this->query('perPage');

        return $perPage === null ? null : (int) $perPage;
    }

    public function order(): string
    {
        $order = $this->query('order');

        return $order === null ? 'desc' : (string) $order;
    }

    public function sort(): string
    {
        $sort = $this->query('sort');

        return $sort === null ? 'published_date' : (string) $sort;
    }

    public function searchWords(): string
    {
        $searchWords = $this->query('searchWords');

        return $searchWords === null ? '' : (string) $searchWords;
    }
}
