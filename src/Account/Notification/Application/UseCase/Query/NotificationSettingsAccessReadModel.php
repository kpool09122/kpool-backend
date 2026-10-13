<?php

declare(strict_types=1);

namespace Source\Account\Notification\Application\UseCase\Query;

readonly class NotificationSettingsAccessReadModel
{
    public function __construct(private bool $canManage)
    {
    }

    public function canManage(): bool
    {
        return $this->canManage;
    }

    /** @return array{canManage: bool} */
    public function toArray(): array
    {
        return ['canManage' => $this->canManage];
    }
}
