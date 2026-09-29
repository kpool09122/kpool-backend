<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Command\AutoCreateWiki;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class AutoCreateWikiRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'agencyIdentifier' => ['nullable', 'uuid'],
            'groupIdentifiers' => ['nullable', 'array'],
            'groupIdentifiers.*' => ['uuid'],
            'talentIdentifiers' => ['nullable', 'array'],
            'talentIdentifiers.*' => ['uuid'],
        ];
    }

    public function resourceType(): string
    {
        return RequestValue::string($this->input('resourceType'));
    }

    public function wikiLanguage(): string
    {
        return RequestValue::string($this->input('language'));
    }

    public function name(): string
    {
        return RequestValue::string($this->input('name'));
    }

    public function slug(): string
    {
        return RequestValue::string($this->input('slug'));
    }

    public function agencyIdentifier(): ?string
    {
        $value = $this->input('agencyIdentifier');

        return $value !== null ? RequestValue::string($value) : null;
    }

    /**
     * @return string[]
     */
    public function groupIdentifiers(): array
    {
        return RequestValue::strings($this->input('groupIdentifiers') ?? []);
    }

    /**
     * @return string[]
     */
    public function talentIdentifiers(): array
    {
        return RequestValue::strings($this->input('talentIdentifiers') ?? []);
    }
}
