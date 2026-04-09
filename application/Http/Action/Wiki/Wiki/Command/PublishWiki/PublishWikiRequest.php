<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Command\PublishWiki;

use Illuminate\Foundation\Http\FormRequest;

class PublishWikiRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'wikiId' => ['required', 'uuid'],
            'principalId' => ['required', 'uuid'],
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

    public function principalId(): string
    {
        return (string) $this->input('principalId');
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

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
