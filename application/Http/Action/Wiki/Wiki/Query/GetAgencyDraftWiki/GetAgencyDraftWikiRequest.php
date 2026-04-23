<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\GetAgencyDraftWiki;

use Illuminate\Foundation\Http\FormRequest;

class GetAgencyDraftWikiRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return [
            ...parent::validationData(),
            'language' => $this->route('language'),
            'slug' => $this->route('slug'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'language' => ['required', 'string', 'in:ja,ko,en'],
            'slug' => ['required', 'string'],
        ];
    }

    public function language(): string
    {
        return (string) $this->route('language');
    }

    public function slug(): string
    {
        return (string) $this->route('slug');
    }
}
