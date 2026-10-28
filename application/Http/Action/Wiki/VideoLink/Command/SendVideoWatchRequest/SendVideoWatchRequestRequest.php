<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\VideoLink\Command\SendVideoWatchRequest;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class SendVideoWatchRequestRequest extends FormRequest
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
