<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\VideoLink\Command\SaveVideoLinks;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class SaveVideoLinksRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resourceType' => ['required', 'string'],
            'wikiIdentifier' => ['required', 'uuid'],
            'videoLinks' => ['present', 'array'],
            'videoLinks.*.url' => ['required', 'url'],
            'videoLinks.*.videoUsage' => ['required', 'string'],
            'videoLinks.*.title' => ['nullable', 'string'],
            'videoLinks.*.displayOrder' => ['required', 'integer'],
            'videoLinks.*.thumbnailUrl' => ['nullable', 'url'],
            'videoLinks.*.publishedAt' => ['nullable', 'date'],
        ];
    }

    public function resourceType(): string
    {
        return RequestValue::string($this->input('resourceType'));
    }

    public function wikiIdentifier(): string
    {
        return RequestValue::string($this->input('wikiIdentifier'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function videoLinks(): array
    {
        return RequestValue::objects($this->input('videoLinks', []));
    }
}
