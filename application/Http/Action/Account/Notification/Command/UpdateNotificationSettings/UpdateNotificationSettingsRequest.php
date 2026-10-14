<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Notification\Command\UpdateNotificationSettings;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipients' => ['present', 'array', 'list', 'max:5'],
            'recipients.*' => ['required', 'array:identityIdentifier,channel'],
            'recipients.*.identityIdentifier' => ['required', 'uuid', 'distinct:ignore_case'],
            'recipients.*.channel' => ['required', 'string', 'in:email'],
        ];
    }

    /** @return list<string> */
    public function identityIdentifiers(): array
    {
        return array_map(static fn (array $recipient): string => RequestValue::string($recipient['identityIdentifier']), RequestValue::objects($this->validated('recipients')));
    }

    public function accountId(): string
    {
        return (string) $this->route('accountId');
    }
}
