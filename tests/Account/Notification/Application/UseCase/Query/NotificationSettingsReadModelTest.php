<?php

declare(strict_types=1);

namespace Tests\Account\Notification\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Account\Notification\Application\UseCase\Query\NotificationSettingsReadModel;

class NotificationSettingsReadModelTest extends TestCase
{
    public function testSerializationContract(): void
    {
        $candidates = [['identityIdentifier' => 'id', 'name' => 'Member', 'email' => 'member@example.com', 'language' => 'ja']];
        $this->assertSame(['recipients' => [], 'candidates' => $candidates, 'maxRecipients' => 1, 'canManage' => true], (new NotificationSettingsReadModel([], $candidates, 1))->toArray());
    }
}
