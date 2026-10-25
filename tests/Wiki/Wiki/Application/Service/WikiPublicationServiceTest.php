<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\Service;

use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\Service\ContributionPointServiceInterface;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Application\Service\VideoLinkCollectionRegistrationServiceInterface;
use Source\Wiki\Wiki\Application\Service\WikiWorkflowNotificationServiceInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki\PublishWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki\PublishWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki\PublishWikiOutput;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWikiAtSameVersion\PublishWikiAtSameVersionInput;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWikiAtSameVersion\PublishWikiAtSameVersionInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\PublishWikiAtSameVersion\PublishWikiAtSameVersionOutput;
use Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki\RollbackWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki\RollbackWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki\RollbackWikiOutput;
use Source\Wiki\Wiki\Domain\Repository\WikiHistoryRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiSnapshotRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\CreateDraftWiki;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class WikiPublicationServiceTest extends TestCase
{
    private function principalIdentifier(): PrincipalIdentifier
    {
        return new PrincipalIdentifier('019a1234-0000-7000-8000-000000000005');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $principal = new Principal($this->principalIdentifier(), new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->with(Mockery::on(fn (PrincipalIdentifier $id): bool => (string) $id === (string) $this->principalIdentifier()))->andReturn($principal);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->andReturnTrue();
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $videoLinkCollectionRegistrationService = Mockery::mock(VideoLinkCollectionRegistrationServiceInterface::class);
        $videoLinkCollectionRegistrationService->shouldReceive('register');
        $this->app()->instance(VideoLinkCollectionRegistrationServiceInterface::class, $videoLinkCollectionRegistrationService);
        $wikiWorkflowNotificationService = Mockery::mock(WikiWorkflowNotificationServiceInterface::class);
        $wikiWorkflowNotificationService->shouldReceive('notifyPublished');
        $this->app()->instance(WikiWorkflowNotificationServiceInterface::class, $wikiWorkflowNotificationService);
        $contributionPointService = Mockery::mock(ContributionPointServiceInterface::class);
        $contributionPointService->shouldReceive('grantPoints');
        $this->app()->instance(ContributionPointServiceInterface::class, $contributionPointService);
    }

    public function testRepeatedCorrectionsThenNextGenerationAndRollbackPreserveEverySnapshot(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $otherWikiId = StrTestHelper::generateUuid();
        $setId = StrTestHelper::generateUuid();
        CreateWiki::create($wikiId, 'group', ['translation_set_identifier' => $setId, 'slug' => 'gr-corrections', 'version' => 1]);
        CreateWiki::create($otherWikiId, 'group', ['translation_set_identifier' => $setId, 'slug' => 'gr-corrections', 'language' => 'ja', 'version' => 1]);
        $otherBefore = DB::table('wikis')->where('id', $otherWikiId)->first();
        $otherBasicBefore = DB::table('wiki_group_basics')->where('wiki_id', $otherWikiId)->first();
        foreach ([1, 2, 3] as $i) {
            $draftId = $this->createDraft($wikiId, $setId, 'correction-' . $i);
            $output = new PublishWikiAtSameVersionOutput();
            DB::transaction(fn () => $this->app()->make(PublishWikiAtSameVersionInterface::class)->process(new PublishWikiAtSameVersionInput(new DraftWikiIdentifier($draftId), $this->principalIdentifier(), ResourceType::GROUP), $output));
            $this->assertSame($wikiId, $output->toArray()['wikiIdentifier']);
            $this->assertSame(1, $output->toArray()['version']);
            $this->assertSame($i, DB::table('wiki_snapshots')->where('wiki_id', $wikiId)->count());
            $this->assertDatabaseMissing('draft_wikis', ['id' => $draftId]);
            $this->assertEquals($otherBefore, DB::table('wikis')->where('id', $otherWikiId)->first());
            $this->assertEquals($otherBasicBefore, DB::table('wiki_group_basics')->where('wiki_id', $otherWikiId)->first());
        }
        $snapshotIds = DB::table('wiki_snapshots')->where('wiki_id', $wikiId)->pluck('id')->all();
        $this->assertCount(3, $snapshotIds);
        $draftId = $this->createDraft($wikiId, $setId, 'next-generation');
        $output = new PublishWikiOutput();
        DB::transaction(fn () => $this->app()->make(PublishWikiInterface::class)->process(new PublishWikiInput(new DraftWikiIdentifier($draftId), $this->principalIdentifier(), ResourceType::GROUP), $output));
        $this->assertSame(2, $output->toArray()['version']);
        $otherDraftId = $this->createDraft($otherWikiId, $setId, 'translated-next-generation', 'ja', true);
        DB::transaction(fn () => $this->app()->make(PublishWikiInterface::class)->process(new PublishWikiInput(new DraftWikiIdentifier($otherDraftId), $this->principalIdentifier(), ResourceType::GROUP), new PublishWikiOutput()));
        $repository = $this->app()->make(WikiSnapshotRepositoryInterface::class);
        $this->assertSame('correction-3', (string) $repository->findByWikiAndVersion(new WikiIdentifier($wikiId), new Version(1))?->basic()->name());
        DB::transaction(fn () => $this->app()->make(RollbackWikiInterface::class)->process(new RollbackWikiInput($this->principalIdentifier(), new WikiIdentifier($wikiId), new Version(1), ResourceType::GROUP), new RollbackWikiOutput()));
        $this->assertDatabaseHas('wiki_group_basics', ['wiki_id' => $wikiId, 'name' => 'correction-3']);
        $this->assertDatabaseHas('wiki_group_basics', ['wiki_id' => $otherWikiId, 'name' => 'TWICE']);
        $this->assertDatabaseHas('wikis', ['id' => $wikiId, 'version' => 3]);
        $this->assertDatabaseHas('wikis', ['id' => $otherWikiId, 'version' => 3]);
        foreach ($snapshotIds as $snapshotId) {
            $this->assertDatabaseHas('wiki_snapshots', ['id' => $snapshotId, 'wiki_id' => $wikiId, 'version' => 1]);
        }
    }

    public function testTransactionRollsBackEarlierSnapshotAndWikiWritesWhenHistorySaveFails(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $setId = StrTestHelper::generateUuid();
        CreateWiki::create($wikiId, 'group', ['translation_set_identifier' => $setId, 'slug' => 'gr-corrections']);
        $draftId = $this->createDraft($wikiId, $setId, 'must-rollback');
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        $wikiHistoryRepository->shouldReceive('save')->once()->andReturnUsing(function () use ($wikiId): void {
            $this->assertDatabaseHas('wiki_group_basics', ['wiki_id' => $wikiId, 'name' => 'must-rollback']);
            $this->assertSame(1, DB::table('wiki_snapshots')->where('wiki_id', $wikiId)->count());

            throw new RuntimeException('history save failed');
        });
        $this->app()->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);

        try {
            DB::transaction(fn () => $this->app()->make(PublishWikiAtSameVersionInterface::class)->process(new PublishWikiAtSameVersionInput(new DraftWikiIdentifier($draftId), $this->principalIdentifier(), ResourceType::GROUP), new PublishWikiAtSameVersionOutput()));
            $this->fail('Expected history save failure');
        } catch (RuntimeException $e) {
            $this->assertSame('history save failed', $e->getMessage());
        }
        $this->assertDatabaseHas('wiki_group_basics', ['wiki_id' => $wikiId, 'name' => 'TWICE']);
        $this->assertSame(0, DB::table('wiki_snapshots')->where('wiki_id', $wikiId)->count());
        $this->assertDatabaseHas('draft_wikis', ['id' => $draftId]);
    }

    private function createDraft(string $wikiId, string $setId, string $name, string $language = 'ko', bool $translated = false): string
    {
        $draftId = StrTestHelper::generateUuid();
        CreateDraftWiki::create($draftId, 'group', [
            'published_wiki_id' => $wikiId,
            'translation_set_identifier' => $setId,
            'slug' => 'gr-corrections',
            'language' => $language,
            'status' => 'approved',
            'editor_id' => (string) $this->principalIdentifier(),
            'approver_id' => (string) $this->principalIdentifier(),
            'translated_at' => $translated ? '2026-10-25 00:00:00' : null,
        ], ['name' => $name]);

        return $draftId;
    }
}
