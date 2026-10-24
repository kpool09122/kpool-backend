<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\VideoLink\Query\ListVideos;

use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class ListVideosRequest extends FormRequest
{
    /**
     * @return array<array-key, mixed>
     */
    public function validationData(): array
    {
        return [
            ...parent::validationData(),
            'language' => $this->route('language'),
            'slug' => $this->route('slug'),
            'resourceType' => $this->route('resourceType'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'minDurationSeconds' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'maxDurationSeconds' => ['nullable', 'integer', 'min:0', 'max:2147483647', ...($this->filled('minDurationSeconds') ? ['gte:minDurationSeconds'] : [])],
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

    public function minDurationSeconds(): ?int
    {
        return $this->input('minDurationSeconds') !== null ? RequestValue::integer($this->input('minDurationSeconds')) : null;
    }

    public function maxDurationSeconds(): ?int
    {
        return $this->input('maxDurationSeconds') !== null ? RequestValue::integer($this->input('maxDurationSeconds')) : null;
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
        return (string) $this->route('resourceType');
    }
}
