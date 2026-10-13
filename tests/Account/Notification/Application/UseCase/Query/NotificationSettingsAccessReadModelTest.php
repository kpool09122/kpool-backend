<?php

declare(strict_types=1);

namespace Tests\Account\Notification\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Account\Notification\Application\UseCase\Query\NotificationSettingsAccessReadModel;

class NotificationSettingsAccessReadModelTest extends TestCase
{
    public function testSerializationContract(): void
    {
        foreach ([false, true] as $allowed) {
            $model = new NotificationSettingsAccessReadModel($allowed);
            $this->assertSame($allowed, $model->canManage());
            $this->assertSame(['canManage' => $allowed], $model->toArray());
        }
    }
}
