<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Command\ApproveWiki;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class ApproveWikiRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<array-key, mixed>
     */
    public function validationData(): array
    {
        return [
            ...parent::validationData(),
            'wikiId' => $this->route('wikiId'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'wikiId' => ['required', 'uuid'],
            'resourceType' => ['required', 'string'],
            'agencyIdentifier' => ['nullable', 'uuid'],
            'groupIdentifiers' => ['nullable', 'array'],
            'groupIdentifiers.*' => ['uuid'],
            'talentIdentifiers' => ['nullable', 'array'],
            'talentIdentifiers.*' => ['uuid'],
        ];
    }

    public function wikiId(): string
    {
        return (string) $this->route('wikiId');
    }

    public function resourceType(): string
    {
        return RequestValue::string($this->input('resourceType'));
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
