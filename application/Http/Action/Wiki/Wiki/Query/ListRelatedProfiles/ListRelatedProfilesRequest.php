<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListRelatedProfiles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class ListRelatedProfilesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return [
            ...parent::validationData(),
            'language' => $this->route('language'),
            'slug' => $this->route('slug'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'language' => ['required', 'string', 'in:ja,ko,en'],
            'slug' => ['required', 'string'],
            'resourceType' => ['required', 'string', Rule::in([
                ResourceType::AGENCY->value,
                ResourceType::GROUP->value,
                ResourceType::TALENT->value,
                ResourceType::SONG->value,
            ])],
        ];
    }

    public function language(): string
    {
        return (string) $this->route('language');
    }

    public function slug(): string
    {
        return (string) $this->route('slug');
    }

    public function resourceType(): string
    {
        return (string) $this->query('resourceType');
    }
}
