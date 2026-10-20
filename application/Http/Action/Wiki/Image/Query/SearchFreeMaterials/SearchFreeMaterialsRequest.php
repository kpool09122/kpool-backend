<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Query\SearchFreeMaterials;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchFreeMaterialsRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $wikiTypes = ['agency', 'group', 'talent', 'song'];

        return [
            'title' => ['nullable', 'required_without:displayName', 'string', 'max:255'],
            'displayName' => ['nullable', 'required_without:title', 'string', 'max:255'],
            'resourceType' => ['required', Rule::in($wikiTypes)],
            'language' => ['required', Rule::in([$this->language()])],
            'relatedWikis' => ['present', 'array', 'list', 'max:100'],
            'relatedWikis.*' => ['required', 'array:identifier,title,resourceType,relationType'],
            'relatedWikis.*.identifier' => ['required', 'uuid'],
            'relatedWikis.*.title' => ['required', 'string', 'max:255'],
            'relatedWikis.*.resourceType' => ['required', Rule::in($wikiTypes)],
            'relatedWikis.*.relationType' => ['required', 'string', 'max:100'],
        ];
    }

    public function title(): ?string
    {
        $value = $this->input('title');

        return $value !== null ? RequestValue::string($value) : null;
    }

    public function displayName(): ?string
    {
        $value = $this->input('displayName');

        return $value !== null ? RequestValue::string($value) : null;
    }

    public function resourceType(): string
    {
        return RequestValue::string($this->input('resourceType'));
    }

    /** @return list<array<string, mixed>> */
    public function relatedWikis(): array
    {
        return RequestValue::objects($this->input('relatedWikis'));
    }
}
