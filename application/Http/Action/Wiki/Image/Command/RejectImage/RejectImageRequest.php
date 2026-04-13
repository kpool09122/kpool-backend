<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Command\RejectImage;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RejectImageRequest extends FormRequest
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
