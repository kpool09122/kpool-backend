<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\GetAgencyDraftWiki;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class GetAgencyDraftWikiRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<array-key, mixed>
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
