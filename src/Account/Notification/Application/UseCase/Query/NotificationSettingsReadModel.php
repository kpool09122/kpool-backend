<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\UseCase\Query;

readonly class NotificationSettingsReadModel
{
    /** @param list<array{identityIdentifier: string, channel: string}> $recipients
     * @param list<array{identityIdentifier: string, name: string, email: string, language: string}> $candidates
     */
    public function __construct(private array $recipients, private array $candidates, private int $maxRecipients)
    {
    }

    /** @return array{recipients: list<array{identityIdentifier: string, channel: string}>, candidates: list<array{identityIdentifier: string, name: string, email: string, language: string}>, maxRecipients: int} */
    public function toArray(): array
    {
        return ['recipients' => $this->recipients, 'candidates' => $this->candidates, 'maxRecipients' => $this->maxRecipients];
    }
}
