<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Command\CreateWiki;

use Illuminate\Foundation\Http\FormRequest;

class CreateWikiRequest extends FormRequest
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
            'slug' => ['required', 'string'],
            'basic' => ['required', 'array'],
            'sections' => ['nullable', 'array'],
            'themeColor' => ['nullable', 'string'],
            'publishedWikiIdentifier' => ['nullable', 'uuid'],
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

    /**
     * @return string
     */
    public function wikiLanguage(): string
    {
        return (string) $this->input('language');
    }

    public function slug(): string
    {
        return (string) $this->input('slug');
    }

    /**
     * @return array<string, mixed>
     */
    public function basic(): array
    {
        return (array) ($this->input('basic') ?? []);
    }

    /**
     * @return array<int, mixed>
     */
    public function sections(): array
    {
        return (array) ($this->input('sections') ?? []);
    }

    public function themeColor(): ?string
    {
        $value = $this->input('themeColor');

        return $value !== null ? (string) $value : null;
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
