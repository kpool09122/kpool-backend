<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\ImageHideRequest\Command\RequestImageHide;

use Illuminate\Foundation\Http\FormRequest;

class RequestImageHideRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'imageIdentifier' => ['required', 'uuid'],
            'requesterName' => ['required', 'string'],
            'requesterEmail' => ['required', 'email'],
            'reason' => ['required', 'string'],
        ];
    }

    public function imageIdentifier(): string
    {
        return (string) $this->input('imageIdentifier');
    }

    public function requesterName(): string
    {
        return (string) $this->input('requesterName');
    }

    public function requesterEmail(): string
    {
        return (string) $this->input('requesterEmail');
    }

    public function reason(): string
    {
        return (string) $this->input('reason');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
