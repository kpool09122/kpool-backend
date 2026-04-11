<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Command\DeleteImage;

use Illuminate\Foundation\Http\FormRequest;

class DeleteImageRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'principalId' => ['required', 'uuid'],
        ];
    }

    public function imageId(): string
    {
        return (string) $this->route('imageId');
    }

    public function principalId(): string
    {
        return (string) $this->input('principalId');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
