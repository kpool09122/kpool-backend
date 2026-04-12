<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Command\ApproveImageHideRequest;

use Illuminate\Foundation\Http\FormRequest;

class ApproveImageHideRequestRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'principalId' => ['required', 'uuid'],
            'reviewerComment' => ['required', 'string'],
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

    public function reviewerComment(): string
    {
        return (string) $this->input('reviewerComment');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
