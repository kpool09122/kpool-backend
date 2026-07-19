<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Command\RequestImageDeletion;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RequestImageDeletionRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'requesterName' => ['required', 'string'],
            'requesterEmail' => ['required', 'email'],
            'reason' => ['required', 'string'],
        ];
    }

    public function imageId(): string
    {
        return (string) $this->route('imageId');
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
}
