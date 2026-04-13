<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Command\RejectImageHideRequest;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RejectImageHideRequestRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reviewerComment' => ['required', 'string'],
        ];
    }

    public function imageId(): string
    {
        return (string) $this->route('imageId');
    }

    public function reviewerComment(): string
    {
        return (string) $this->input('reviewerComment');
    }
}
