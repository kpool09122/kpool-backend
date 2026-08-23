<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListRelatedWikis;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class ListRelatedWikisRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return [
            ...parent::validationData(),
            'resourceType' => $this->route('resourceType'),
            'translationSetIdentifier' => $this->route('translationSetIdentifier'),
        ];
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'resourceType' => ['required', 'string', Rule::in([
                ResourceType::AGENCY->value,
                ResourceType::TALENT->value,
            ])],
            'translationSetIdentifier' => ['required', 'uuid'],
        ];
    }

    public function resourceType(): string
    {
        return (string) $this->route('resourceType');
    }

    public function translationSetIdentifier(): string
    {
        return (string) $this->route('translationSetIdentifier');
    }
}
