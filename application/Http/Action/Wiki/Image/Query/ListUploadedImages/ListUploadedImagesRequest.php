<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Query\ListUploadedImages;

use Illuminate\Foundation\Http\FormRequest;

class ListUploadedImagesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'translationSetIdentifier' => ['required', 'uuid'],
        ];
    }

    public function perPage(): ?int
    {
        $perPage = $this->query('perPage');

        return $perPage === null ? null : (int) $perPage;
    }

    public function translationSetIdentifier(): string
    {
        return (string) $this->query('translationSetIdentifier');
    }
}
