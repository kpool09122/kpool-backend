<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\PromotionHistory;
use Source\Wiki\Principal\Domain\Repository\PromotionHistoryRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PromotionHistoryIdentifier;
use Source\Wiki\Principal\Infrastructure\Repository\PromotionHistoryRepository;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreatePromotionHistory;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PromotionHistoryRepositoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = $this->app->make(PromotionHistoryRepositoryInterface::class);
        $this->assertInstanceOf(PromotionHistoryRepository::class, $repository);
    }

    /**
     * 正常系: 正しくPromotionHistoryを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $historyId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $processedAt = new DateTimeImmutable('2024-01-15 10:00:00');

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );

        $history = new PromotionHistory(
            new PromotionHistoryIdentifier($historyId),
            new PrincipalIdentifier($principalId),
            'GENERAL',
            'COLLABORATOR',
            'Promotion due to high contribution points',
            $processedAt,
        );

        $repository = $this->app->make(PromotionHistoryRepositoryInterface::class);
        $repository->save($history);

        $this->assertDatabaseHas('promotion_histories', [
            'id' => $historyId,
            'principal_id' => $principalId,
            'from_role' => 'GENERAL',
            'to_role' => 'COLLABORATOR',
            'reason' => 'Promotion due to high contribution points',
        ]);
    }

    /**
     * 正常系: reasonがnullでも保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNullReason(): void
    {
        $historyId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );

        $history = new PromotionHistory(
            new PromotionHistoryIdentifier($historyId),
            new PrincipalIdentifier($principalId),
            'COLLABORATOR',
            'GENERAL',
            null,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(PromotionHistoryRepositoryInterface::class);
        $repository->save($history);

        $this->assertDatabaseHas('promotion_histories', [
            'id' => $historyId,
            'principal_id' => $principalId,
            'from_role' => 'COLLABORATOR',
            'to_role' => 'GENERAL',
            'reason' => null,
        ]);
    }

    /**
     * 正常系: PrincipalIdでPromotionHistoryを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPrincipal(): void
    {
        $historyId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        $processedAt = new DateTimeImmutable('2024-01-15 10:00:00');

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );
        CreatePromotionHistory::create(
            new PromotionHistoryIdentifier($historyId),
            new PrincipalIdentifier($principalId),
            [
                'from_role' => 'GENERAL',
                'to_role' => 'COLLABORATOR',
                'reason' => 'Promotion reason',
                'processed_at' => $processedAt,
            ]
        );

        $repository = $this->app->make(PromotionHistoryRepositoryInterface::class);
        $results = $repository->findByPrincipal(new PrincipalIdentifier($principalId));

        $this->assertCount(1, $results);
        $this->assertSame($historyId, (string) $results[0]->id());
        $this->assertSame($principalId, (string) $results[0]->principalIdentifier());
        $this->assertSame('GENERAL', $results[0]->fromRole());
        $this->assertSame('COLLABORATOR', $results[0]->toRole());
        $this->assertSame('Promotion reason', $results[0]->reason());
        $this->assertSame('2024-01-15', $results[0]->processedAt()->format('Y-m-d'));
    }

    /**
     * 正常系: 複数のPromotionHistoryを降順で取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPrincipalReturnsMultipleOrderedByProcessedAtDesc(): void
    {
        $principalId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();

        CreateIdentity::create(new IdentityIdentifier($identityId));
        CreatePrincipal::create(
            new PrincipalIdentifier($principalId),
            new IdentityIdentifier($identityId),
        );

        // 異なる日付で3件作成
        CreatePromotionHistory::create(
            new PromotionHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            [
                'from_role' => 'GENERAL',
                'to_role' => 'COLLABORATOR',
                'reason' => 'First promotion',
                'processed_at' => new DateTimeImmutable('2024-01-01 10:00:00'),
            ]
        );
        CreatePromotionHistory::create(
            new PromotionHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            [
                'from_role' => 'COLLABORATOR',
                'to_role' => 'GENERAL',
                'reason' => 'Demotion',
                'processed_at' => new DateTimeImmutable('2024-02-01 10:00:00'),
            ]
        );
        CreatePromotionHistory::create(
            new PromotionHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId),
            [
                'from_role' => 'GENERAL',
                'to_role' => 'COLLABORATOR',
                'reason' => 'Second promotion',
                'processed_at' => new DateTimeImmutable('2024-03-01 10:00:00'),
            ]
        );

        $repository = $this->app->make(PromotionHistoryRepositoryInterface::class);
        $results = $repository->findByPrincipal(new PrincipalIdentifier($principalId));

        $this->assertCount(3, $results);
        // 降順で取得されることを確認
        $this->assertSame('2024-03-01', $results[0]->processedAt()->format('Y-m-d'));
        $this->assertSame('2024-02-01', $results[1]->processedAt()->format('Y-m-d'));
        $this->assertSame('2024-01-01', $results[2]->processedAt()->format('Y-m-d'));
    }

    /**
     * 正常系: 該当するPromotionHistoryが存在しない場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPrincipalWhenNotFound(): void
    {
        $repository = $this->app->make(PromotionHistoryRepositoryInterface::class);

        $results = $repository->findByPrincipal(
            new PrincipalIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertSame([], $results);
    }

    /**
     * 正常系: 異なるPrincipalのPromotionHistoryは取得されないこと
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPrincipalDoesNotReturnOtherPrincipals(): void
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

        // 異なるPrincipalで作成
        CreatePromotionHistory::create(
            new PromotionHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId1),
            [
                'from_role' => 'GENERAL',
                'to_role' => 'COLLABORATOR',
                'reason' => 'Principal 1 promotion',
            ]
        );
        CreatePromotionHistory::create(
            new PromotionHistoryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier($principalId2),
            [
                'from_role' => 'GENERAL',
                'to_role' => 'COLLABORATOR',
                'reason' => 'Principal 2 promotion',
            ]
        );

        $repository = $this->app->make(PromotionHistoryRepositoryInterface::class);

        // Principal1のみ取得
        $results = $repository->findByPrincipal(new PrincipalIdentifier($principalId1));

        $this->assertCount(1, $results);
        $this->assertSame($principalId1, (string) $results[0]->principalIdentifier());
    }
}
