<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\GetTalentDraftWiki;

use Illuminate\Foundation\Http\FormRequest;

class GetTalentDraftWikiRequest extends FormRequest
{
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
