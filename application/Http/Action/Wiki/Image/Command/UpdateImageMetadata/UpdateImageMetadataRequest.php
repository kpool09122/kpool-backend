<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Command\UpdateImageMetadata;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Wiki\Image\Domain\ValueObject\ImageAuthor;
use Source\Wiki\Image\Domain\ValueObject\ImageLicense;

class UpdateImageMetadataRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sourceName' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:' . ImageAuthor::MAX_LENGTH],
            'license' => ['required', Rule::enum(ImageLicense::class)],
            'altText' => ['present', 'nullable', 'string', 'max:512'],
            'displayOrder' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'sourceUrl' => ['prohibited'],
            'base64EncodedImage' => ['prohibited'],
        ];
    }

    public function imageId(): string
    {
        return (string) $this->route('imageId');
    }
}
