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
    public function testCountriesUseImmutableRegistrationOriginAndHistoricalWithdrawals(): void
    {
        $japan = $this->identity('2020-01-01');
        Identity::query()->whereKey($japan)->update(['registration_country' => 'JP']);
        $this->identity('2020-01-01');
        $late = $this->identity('2020-01-02');
        Identity::query()->whereKey($late)->update(['registration_country' => 'US']);
        $this->archive('2019-01-01', '2020-01-03');
        ArchivedIdentity::query()->where('archived_at', '2020-01-03')->update(['registration_country' => 'KR']);
        $query = new RegisteredIdentityCountQueryService();
        $cutoff = new DateTimeImmutable('2020-01-02T00:00:00Z');
        $countries = $query->countByCountryBefore($cutoff);
        self::assertSame(['' => '1', 'JP' => '1', 'KR' => '1'], array_replace(array_fill_keys(['', 'JP', 'KR'], ''), $countries));
    }

    public function testHistoricalBalanceIncludesLaterWithdrawalsAndExcludesEndBoundaryRegistrations(): void
    {
        $this->identity('2020-01-01 23:59:59');
        $this->identity('2020-01-02 00:00:00');
        $this->archive('2019-01-01', '2020-01-02');
        $this->archive('2019-01-01', '2020-01-01 23:59:59');
        $this->archive('2020-01-02', '2020-01-03');
        self::assertSame(['' => '2'], (new RegisteredIdentityCountQueryService())->countByCountryBefore(new DateTimeImmutable('2020-01-02T00:00:00Z')));
    }

    private function identity(string $created): string
    {
        $model = new Identity();
        $model->forceFill(['id' => StrTestHelper::generateUuid(), 'identity_name' => 'test', 'email' => StrTestHelper::generateUuid().'@example.test', 'language' => 'ja', 'created_at' => $created, 'updated_at' => $created])->save();

        return $model->id;
    }

    private function archive(string $created, string $withdrawn): void
    {
        ArchivedIdentity::query()->create(['id' => StrTestHelper::generateUuid(), 'identity_id' => StrTestHelper::generateUuid(), 'language' => 'ja', 'identity_created_at' => $created, 'archived_at' => $withdrawn]);
    }
}
