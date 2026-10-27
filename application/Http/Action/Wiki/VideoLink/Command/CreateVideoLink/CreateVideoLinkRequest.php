<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\VideoLink\Command\CreateVideoLink;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;

class CreateVideoLinkRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'resourceType' => ['required', Rule::in(['agency', 'group', 'song', 'talent'])],
            'wikiIdentifier' => ['required', 'uuid'],
            'url' => ['required', 'url:https', 'max:255'],
            'videoUsage' => ['required', Rule::in(array_map(static fn (VideoUsage $usage): string => $usage->value, array_filter(VideoUsage::cases(), static fn (VideoUsage $usage): bool => $usage->isManual())))],
            'title' => ['required', 'string', 'max:255'],
            'durationSeconds' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'thumbnailUrl' => ['nullable', 'url:http,https', 'max:255'],
            'publishedAt' => ['nullable', 'date'],
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

    public function url(): string
    {
        return RequestValue::string($this->input('url'));
    }

    public function videoUsage(): string
    {
        return RequestValue::string($this->input('videoUsage'));
    }

    public function title(): string
    {
        return RequestValue::string($this->input('title'));
    }

    public function durationSeconds(): ?int
    {
        return $this->input('durationSeconds') !== null ? RequestValue::integer($this->input('durationSeconds')) : null;
    }

    public function thumbnailUrl(): ?string
    {
        return $this->input('thumbnailUrl') !== null ? RequestValue::string($this->input('thumbnailUrl')) : null;
    }

    public function publishedAt(): ?string
    {
        return $this->input('publishedAt') !== null ? RequestValue::string($this->input('publishedAt')) : null;
    }
}
