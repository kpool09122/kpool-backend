<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\ImageHideRequest\Command\ApproveImageHideRequest;

use Illuminate\Foundation\Http\FormRequest;

class ApproveImageHideRequestRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'requestId' => ['required', 'uuid'],
            'principalId' => ['required', 'uuid'],
            'reviewerComment' => ['required', 'string'],
        ];
    }

    public function requestId(): string
    {
        return (string) $this->input('requestId');
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
