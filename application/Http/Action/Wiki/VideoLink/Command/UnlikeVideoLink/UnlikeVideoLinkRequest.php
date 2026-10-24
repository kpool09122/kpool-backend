<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\VideoLink\Command\UnlikeVideoLink;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class UnlikeVideoLinkRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    public function videoLinkId(): string
    {
        return (string) $this->route('videoLinkId');
    }
}
