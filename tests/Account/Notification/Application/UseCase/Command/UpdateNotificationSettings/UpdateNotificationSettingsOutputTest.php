<?php

declare(strict_types=1);

namespace Tests\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings;

use PHPUnit\Framework\TestCase;
use Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings\UpdateNotificationSettingsOutput;

class UpdateNotificationSettingsOutputTest extends TestCase
{
    public function testOutputIsAnEmptyArray(): void
    {
        $this->assertSame([], (new UpdateNotificationSettingsOutput())->toArray());
    }
}
