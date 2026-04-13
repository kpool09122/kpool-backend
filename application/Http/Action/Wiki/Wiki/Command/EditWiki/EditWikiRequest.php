<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Command\EditWiki;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class EditWikiRequest extends FormRequest
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
            'basic' => ['required', 'array'],
            'sections' => ['nullable', 'array'],
            'themeColor' => ['nullable', 'string'],
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
