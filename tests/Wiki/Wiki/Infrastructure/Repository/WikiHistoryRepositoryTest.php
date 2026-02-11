<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\WikiHistory;
use Source\Wiki\Wiki\Domain\Repository\WikiHistoryRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiHistoryIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Wiki\Infrastructure\Repository\WikiHistoryRepository;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WikiHistoryRepositoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = $this->app->make(WikiHistoryRepositoryInterface::class);
        $this->assertInstanceOf(WikiHistoryRepository::class, $repository);
    }

    /**
     * 正常系: 全フィールドを指定してWikiHistoryを保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithAllFields(): void
    {
        $historyId = StrTestHelper::generateUuid();
        $actorId = StrTestHelper::generateUuid();
        $submitterId = StrTestHelper::generateUuid();
        $wikiId = StrTestHelper::generateUuid();
        $draftWikiId = StrTestHelper::generateUuid();
        $recordedAt = new DateTimeImmutable('2024-06-01 12:00:00');

        $wikiHistory = new WikiHistory(
            new WikiHistoryIdentifier($historyId),
            HistoryActionType::DraftStatusChange,
            new PrincipalIdentifier($actorId),
            new PrincipalIdentifier($submitterId),
            new WikiIdentifier($wikiId),
            new DraftWikiIdentifier($draftWikiId),
            ApprovalStatus::Pending,
            ApprovalStatus::Approved,
            new Version(1),
            new Version(2),
            new Name('TWICE'),
            $recordedAt,
        );

        $repository = $this->app->make(WikiHistoryRepositoryInterface::class);
        $repository->save($wikiHistory);

        $this->assertDatabaseHas('wiki_histories', [
            'id' => $historyId,
            'action_type' => HistoryActionType::DraftStatusChange->value,
            'actor_id' => $actorId,
            'submitter_id' => $submitterId,
            'wiki_id' => $wikiId,
            'draft_wiki_id' => $draftWikiId,
            'from_status' => ApprovalStatus::Pending->value,
            'to_status' => ApprovalStatus::Approved->value,
            'from_version' => 1,
            'to_version' => 2,
            'subject_name' => 'TWICE',
        ]);
    }

    /**
     * 正常系: nullable フィールドがnullのままWikiHistoryを保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNullableFields(): void
    {
        $historyId = StrTestHelper::generateUuid();
        $actorId = StrTestHelper::generateUuid();
        $wikiId = StrTestHelper::generateUuid();
        $recordedAt = new DateTimeImmutable('2024-06-01 12:00:00');

        $wikiHistory = new WikiHistory(
            new WikiHistoryIdentifier($historyId),
            HistoryActionType::Publish,
            new PrincipalIdentifier($actorId),
            null,
            new WikiIdentifier($wikiId),
            null,
            null,
            null,
            null,
            null,
            new Name('JYP Entertainment'),
            $recordedAt,
        );

        $repository = $this->app->make(WikiHistoryRepositoryInterface::class);
        $repository->save($wikiHistory);

        $this->assertDatabaseHas('wiki_histories', [
            'id' => $historyId,
            'action_type' => HistoryActionType::Publish->value,
            'actor_id' => $actorId,
            'submitter_id' => null,
            'wiki_id' => $wikiId,
            'draft_wiki_id' => null,
            'from_status' => null,
            'to_status' => null,
            'from_version' => null,
            'to_version' => null,
            'subject_name' => 'JYP Entertainment',
        ]);
    }

    /**
     * 正常系: Rollback アクションタイプでWikiHistoryを保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithRollbackAction(): void
    {
        $historyId = StrTestHelper::generateUuid();
        $actorId = StrTestHelper::generateUuid();
        $wikiId = StrTestHelper::generateUuid();
        $draftWikiId = StrTestHelper::generateUuid();
        $recordedAt = new DateTimeImmutable('2024-07-15 09:30:00');

        $wikiHistory = new WikiHistory(
            new WikiHistoryIdentifier($historyId),
            HistoryActionType::Rollback,
            new PrincipalIdentifier($actorId),
            null,
            new WikiIdentifier($wikiId),
            new DraftWikiIdentifier($draftWikiId),
            null,
            null,
            new Version(3),
            new Version(2),
            new Name('채영'),
            $recordedAt,
        );

        $repository = $this->app->make(WikiHistoryRepositoryInterface::class);
        $repository->save($wikiHistory);

        $this->assertDatabaseHas('wiki_histories', [
            'id' => $historyId,
            'action_type' => HistoryActionType::Rollback->value,
            'actor_id' => $actorId,
            'wiki_id' => $wikiId,
            'draft_wiki_id' => $draftWikiId,
            'from_version' => 3,
            'to_version' => 2,
            'subject_name' => '채영',
        ]);
    }
}
