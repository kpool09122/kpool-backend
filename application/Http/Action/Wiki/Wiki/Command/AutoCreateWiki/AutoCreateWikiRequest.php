<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Command\AutoCreateWiki;

use Illuminate\Foundation\Http\FormRequest;

class AutoCreateWikiRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'principalId' => ['required', 'uuid'],
            'resourceType' => ['required', 'string'],
            'language' => ['required', 'string'],
            'name' => ['required', 'string'],
            'agencyIdentifier' => ['nullable', 'uuid'],
            'groupIdentifiers' => ['nullable', 'array'],
            'groupIdentifiers.*' => ['uuid'],
            'talentIdentifiers' => ['nullable', 'array'],
            'talentIdentifiers.*' => ['uuid'],
        ];
    }

    public function principalId(): string
    {
        return (string) $this->input('principalId');
    }

    public function resourceType(): string
    {
        return (string) $this->input('resourceType');
    }

    public function wikiLanguage(): string
    {
        return (string) $this->input('language');
    }

    public function name(): string
    {
        return (string) $this->input('name');
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
