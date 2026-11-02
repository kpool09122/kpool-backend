<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Infrastructure\Service;

use Application\Models\Identity\ArchivedIdentity;
use Application\Models\Identity\Identity;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\App\Infrastructure\Service\RegisteredIdentityCountQueryService;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class RegisteredIdentityCountQueryServiceTest extends TestCase
{
    public function testHistoricalBalanceIncludesLaterWithdrawalsAndExcludesEndBoundaryRegistrations(): void
    {
        $this->identity('2020-01-01 23:59:59');
        $this->identity('2020-01-02 00:00:00');
        $this->archive('2019-01-01', '2020-01-02');
        $this->archive('2019-01-01', '2020-01-01 23:59:59');
        $this->archive('2020-01-02', '2020-01-03');
        self::assertSame('2', (new RegisteredIdentityCountQueryService())->countBefore(new DateTimeImmutable('2020-01-02T00:00:00Z')));
    }

    private function identity(string $created): void
    {
        $model = new Identity();
        $model->forceFill(['id' => StrTestHelper::generateUuid(), 'identity_name' => 'test', 'email' => StrTestHelper::generateUuid().'@example.test', 'language' => 'ja', 'created_at' => $created, 'updated_at' => $created])->save();
    }

    private function archive(string $created, string $withdrawn): void
    {
        ArchivedIdentity::query()->create(['id' => StrTestHelper::generateUuid(), 'identity_id' => StrTestHelper::generateUuid(), 'language' => 'ja', 'identity_created_at' => $created, 'archived_at' => $withdrawn]);
    }
}
