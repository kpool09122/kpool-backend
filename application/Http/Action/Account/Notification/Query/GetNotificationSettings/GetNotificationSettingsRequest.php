<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Notification\Query\GetNotificationSettings;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class GetNotificationSettingsRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function accountId(): string
    {
        return (string) $this->route('accountId');
    }
}
