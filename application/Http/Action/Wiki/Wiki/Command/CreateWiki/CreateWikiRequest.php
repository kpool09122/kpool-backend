<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Command\CreateWiki;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class CreateWikiRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resourceType' => ['required', 'string'],
            'language' => ['required', 'string'],
            'slug' => ['required', 'string'],
            'basic' => ['required', 'array'],
            'sections' => ['nullable', 'array'],
            'themeColor' => ['nullable', 'string'],
            'imageIdentifier' => ['nullable', 'uuid'],
            'title' => ['nullable', 'string', 'max:40'],
            'metaDescription' => ['nullable', 'string', 'max:140'],
            'keywords' => ['nullable', 'array', 'max:5'],
            'keywords.*' => ['string', 'max:20'],
            'publishedWikiIdentifier' => ['nullable', 'uuid'],
            'agencyIdentifier' => ['nullable', 'uuid'],
            'groupIdentifiers' => ['nullable', 'array'],
            'groupIdentifiers.*' => ['uuid'],
            'talentIdentifiers' => ['nullable', 'array'],
            'talentIdentifiers.*' => ['uuid'],
        ];
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

    public function imageIdentifier(): ?string
    {
        $value = $this->input('imageIdentifier');

        return $value !== null ? (string) $value : null;
    }

    public function title(): ?string
    {
        $value = $this->input('title');

        return $value !== null ? (string) $value : null;
    }

    public function metaDescription(): ?string
    {
        $value = $this->input('metaDescription');

        return $value !== null ? (string) $value : null;
    }

    /**
     * @return list<string>|null
     */
    public function keywords(): ?array
    {
        $value = $this->input('keywords');

        return is_array($value) ? array_values(array_map('strval', $value)) : null;
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
