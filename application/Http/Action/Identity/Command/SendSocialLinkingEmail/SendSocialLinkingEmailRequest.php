<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\SendSocialLinkingEmail;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class SendSocialLinkingEmailRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [

        ];
    }
}
