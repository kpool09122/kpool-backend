<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\GetSongDraftWiki;

use Illuminate\Foundation\Http\FormRequest;

class GetSongDraftWikiRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return [
            ...parent::validationData(),
            'wikiIdentifier' => $this->route('wikiIdentifier'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'wikiIdentifier' => ['required', 'uuid'],
        ];
    }

    public function wikiIdentifier(): string
    {
        return (string) $this->route('wikiIdentifier');
    }
}
