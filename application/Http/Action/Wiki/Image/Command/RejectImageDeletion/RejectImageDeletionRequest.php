<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Command\RejectImageDeletion;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class RejectImageDeletionRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejectReason' => ['required', 'string'],
        ];
    }

    public function imageId(): string
    {
        return (string) $this->route('imageId');
    }

    public function rejectReason(): string
    {
        return RequestValue::string($this->input('rejectReason'));
    }
}
