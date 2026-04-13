<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Command\PublishWiki;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class PublishWikiRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'wikiId' => ['required', 'uuid'],
            'resourceType' => ['required', 'string'],
            'publishedWikiIdentifier' => ['nullable', 'uuid'],
            'agencyIdentifier' => ['nullable', 'uuid'],
            'groupIdentifiers' => ['nullable', 'array'],
            'groupIdentifiers.*' => ['uuid'],
            'talentIdentifiers' => ['nullable', 'array'],
            'talentIdentifiers.*' => ['uuid'],
        ];
    }

    public function wikiId(): string
    {
        return (string) $this->input('wikiId');
    }

    public function resourceType(): string
    {
        return (string) $this->input('resourceType');
    }

    public function publishedWikiIdentifier(): ?string
    {
        $value = $this->input('publishedWikiIdentifier');

        return $value !== null ? (string) $value : null;
    }

    public function agencyIdentifier(): ?string
    {
        $value = $this->input('agencyIdentifier');

        return $value !== null ? (string) $value : null;
    }

    /**
     * @return string[]
     */
    public function groupIdentifiers(): array
    {
        return (array) ($this->input('groupIdentifiers') ?? []);
    }

    /**
     * @return string[]
     */
    public function talentIdentifiers(): array
    {
        return (array) ($this->input('talentIdentifiers') ?? []);
    }
}
