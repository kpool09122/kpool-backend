<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Command\ApproveImageDeletion;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class ApproveImageDeletionRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function imageId(): string
    {
        return (string) $this->route('imageId');
    }

}
