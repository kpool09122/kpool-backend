<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\VideoLink\Command\SaveVideoLinks;

use Illuminate\Foundation\Http\FormRequest;

class SaveVideoLinksRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'principalId' => ['required', 'uuid'],
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

    public function principalId(): string
    {
        return (string) $this->input('principalId');
    }

    public function resourceType(): string
    {
        return (string) $this->input('resourceType');
    }

    public function wikiIdentifier(): string
    {
        return (string) $this->input('wikiIdentifier');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function videoLinks(): array
    {
        /** @var array<int, array<string, mixed>> $videoLinks */
        $videoLinks = $this->input('videoLinks', []);

        return $videoLinks;
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
