<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Command\UploadImage;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class UploadImageRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'publishedImageIdentifier' => ['nullable', 'uuid'],
            'resourceType' => ['required', 'string'],
            'wikiIdentifier' => ['required', 'uuid'],
            'base64EncodedImage' => ['required', 'string'],
            'imageUsage' => ['required', 'string'],
            'displayOrder' => ['required', 'integer'],
            'sourceUrl' => ['required', 'string'],
            'sourceName' => ['required', 'string'],
            'altText' => ['required', 'string'],
            'agreedToTermsAt' => ['required', 'date'],
        ];
    }

    public function publishedImageIdentifier(): ?string
    {
        $value = $this->input('publishedImageIdentifier');

        return $value !== null ? (string) $value : null;
    }

    public function resourceType(): string
    {
        return (string) $this->input('resourceType');
    }

    public function wikiIdentifier(): string
    {
        return (string) $this->input('wikiIdentifier');
    }

    public function base64EncodedImage(): string
    {
        return (string) $this->input('base64EncodedImage');
    }

    public function imageUsage(): string
    {
        return (string) $this->input('imageUsage');
    }

    public function displayOrder(): string
    {
        return (string) $this->input('displayOrder');
    }

    public function sourceUrl(): string
    {
        return (string) $this->input('sourceUrl');
    }

    public function sourceName(): string
    {
        return (string) $this->input('sourceName');
    }

    public function altText(): string
    {
        return (string) $this->input('altText');
    }

    public function agreedToTermsAt(): string
    {
        return (string) $this->input('agreedToTermsAt');
    }
}
