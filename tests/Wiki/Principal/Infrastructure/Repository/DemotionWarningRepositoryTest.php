<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\DemotionWarning;
use Source\Wiki\Principal\Domain\Repository\DemotionWarningRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\DemotionWarningIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\WarningCount;
use Source\Wiki\Principal\Domain\ValueObject\YearMonth;
use Source\Wiki\Principal\Infrastructure\Repository\DemotionWarningRepository;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateDemotionWarning;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DemotionWarningRepositoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = $this->app->make(DemotionWarningRepositoryInterface::class);
        $this->assertInstanceOf(DemotionWarningRepository::class, $repository);
    }

    /**
     * 正常系: 正しくDemotionWarningを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $warningId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $yearMonth = '2024-01';

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );

        $warning = new DemotionWarning(
            new DemotionWarningIdentifier($warningId),
            new PrincipalIdentifier($principalId),
            new WarningCount(1),
            new YearMonth($yearMonth),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(DemotionWarningRepositoryInterface::class);
        $repository->save($warning);

        $this->assertDatabaseHas('demotion_warnings', [
            'id' => $warningId,
            'principal_id' => $principalId,
            'warning_count' => 1,
            'last_warning_month' => $yearMonth,
        ]);
    }

    /**
     * 正常系: 同じPrincipalの既存のDemotionWarningを更新できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveUpdatesExisting(): void
    {
        $warningId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );
        CreateDemotionWarning::create(
            new DemotionWarningIdentifier($warningId),
            new PrincipalIdentifier($principalId),
            [
                'warning_count' => 1,
                'last_warning_month' => '2024-01',
            ]
        );

        $this->assertDatabaseHas('demotion_warnings', [
            'id' => $warningId,
            'principal_id' => $principalId,
            'warning_count' => 1,
            'last_warning_month' => '2024-01',
        ]);

        // 同じPrincipalで更新（warning_countをインクリメント）
        $repository = $this->app->make(DemotionWarningRepositoryInterface::class);
        $warning = new DemotionWarning(
            new DemotionWarningIdentifier($warningId),
            new PrincipalIdentifier($principalId),
            new WarningCount(2),
            new YearMonth('2024-02'),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );
        $repository->save($warning);

        $this->assertDatabaseHas('demotion_warnings', [
            'id' => $warningId,
            'principal_id' => $principalId,
            'warning_count' => 2,
            'last_warning_month' => '2024-02',
        ]);

        // レコードが1件のままであること
        $this->assertDatabaseCount('demotion_warnings', 1);
    }

    /**
     * 正常系: PrincipalIdでDemotionWarningを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPrincipal(): void
    {
        $warningId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $yearMonth = '2024-03';

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );
        CreateDemotionWarning::create(
            new DemotionWarningIdentifier($warningId),
            new PrincipalIdentifier($principalId),
            [
                'warning_count' => 2,
                'last_warning_month' => $yearMonth,
            ]
        );

        $repository = $this->app->make(DemotionWarningRepositoryInterface::class);
        $result = $repository->findByPrincipal(new PrincipalIdentifier($principalId));

        $this->assertNotNull($result);
        $this->assertSame($warningId, (string) $result->id());
        $this->assertSame($principalId, (string) $result->principalIdentifier());
        $this->assertSame(2, $result->warningCount()->value());
        $this->assertSame($yearMonth, (string) $result->lastWarningMonth());
    }

    /**
     * 正常系: 該当するDemotionWarningが存在しない場合、nullが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPrincipalWhenNotFound(): void
    {
        $repository = $this->app->make(DemotionWarningRepositoryInterface::class);

        $result = $repository->findByPrincipal(
            new PrincipalIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertNull($result);
    }

    /**
     * 正常系: DemotionWarningを削除できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $warningId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );
        CreateDemotionWarning::create(
            new DemotionWarningIdentifier($warningId),
            new PrincipalIdentifier($principalId),
        );

        // 削除前の確認
        $this->assertDatabaseHas('demotion_warnings', ['id' => $warningId]);

        $repository = $this->app->make(DemotionWarningRepositoryInterface::class);
        $warning = new DemotionWarning(
            new DemotionWarningIdentifier($warningId),
            new PrincipalIdentifier($principalId),
            new WarningCount(1),
            new YearMonth('2024-01'),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );
        $repository->delete($warning);

        // 削除後の確認
        $this->assertDatabaseMissing('demotion_warnings', ['id' => $warningId]);
    }

    /**
     * 正常系: すべてのDemotionWarningを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindAll(): void
    {
        $principalId1 = StrTestHelper::generateUuid();
        $principalId2 = StrTestHelper::generateUuid();
        $identityId1 = StrTestHelper::generateUuid();
        $identityId2 = StrTestHelper::generateUuid();

        CreateIdentity::create(new IdentityIdentifier($identityId1), ['email' => 'test1@example.com']);
        CreateIdentity::create(new IdentityIdentifier($identityId2), ['email' => 'test2@example.com']);
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId1),
            new IdentityIdentifier($identityId1),
        );
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId2),
            new IdentityIdentifier($identityId2),
        );

        // 2件作成
        CreateDemotionWarning::create(
            new DemotionWarningIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId1),
            [
                'warning_count' => 1,
                'last_warning_month' => '2024-01',
            ]
        );
        CreateDemotionWarning::create(
            new DemotionWarningIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId2),
            [
                'warning_count' => 2,
                'last_warning_month' => '2024-02',
            ]
        );

        $repository = $this->app->make(DemotionWarningRepositoryInterface::class);
        $results = $repository->findAll();

        $this->assertCount(2, $results);
        $principalIds = array_map(
            static fn (DemotionWarning $w) => (string) $w->principalIdentifier(),
            $results
        );
        $this->assertContains($principalId1, $principalIds);
        $this->assertContains($principalId2, $principalIds);
    }

    /**
     * 正常系: DemotionWarningが存在しない場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindAllWhenEmpty(): void
    {
        $repository = $this->app->make(DemotionWarningRepositoryInterface::class);

        $results = $repository->findAll();

        $this->assertSame([], $results);
    }
}
