<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\WithdrawWiki;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Command\WithdrawWiki\WithdrawWiki;
use Source\Wiki\Wiki\Application\UseCase\Command\WithdrawWiki\WithdrawWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\WithdrawWiki\WithdrawWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\WithdrawWiki\WithdrawWikiOutput;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Entity\WikiHistory;
use Source\Wiki\Wiki\Domain\Factory\WikiHistoryFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiHistoryRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\HexColor;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiHistoryIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WithdrawWikiTest extends TestCase
{
    public function test__construct(): void
    {
        $this->app->instance(DraftWikiRepositoryInterface::class, Mockery::mock(DraftWikiRepositoryInterface::class));
        $this->app->instance(WikiHistoryRepositoryInterface::class, Mockery::mock(WikiHistoryRepositoryInterface::class));
        $this->app->instance(WikiHistoryFactoryInterface::class, Mockery::mock(WikiHistoryFactoryInterface::class));
        $this->app->instance(PrincipalRepositoryInterface::class, Mockery::mock(PrincipalRepositoryInterface::class));

        $withdrawWiki = $this->app->make(WithdrawWikiInterface::class);

        $this->assertInstanceOf(WithdrawWiki::class, $withdrawWiki);
    }

    /**
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithdrawsUnderReviewDraftWiki(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $draftWiki = $this->createDraftWiki(ApprovalStatus::UnderReview, $principalIdentifier);
        $history = $this->createHistory($draftWiki, $principalIdentifier, ApprovalStatus::UnderReview, ApprovalStatus::Pending);
        $input = $this->createInput($draftWiki->wikiIdentifier(), $principalIdentifier);

        $this->bindRepositoriesForPolicyResult($draftWiki, $principalIdentifier, true, true, $history);

        $output = new WithdrawWikiOutput();
        $this->app->make(WithdrawWikiInterface::class)->process($input, $output);

        $this->assertSame(ApprovalStatus::Pending->value, $output->toArray()['status']);
    }

    /**
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWikiNotFound(): void
    {
        $wikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = $this->createInput($wikiIdentifier, $principalIdentifier);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')->once()->with($wikiIdentifier)->andReturn(null);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiHistoryRepositoryInterface::class, Mockery::mock(WikiHistoryRepositoryInterface::class));
        $this->app->instance(WikiHistoryFactoryInterface::class, Mockery::mock(WikiHistoryFactoryInterface::class));

        $this->expectException(WikiNotFoundException::class);
        $this->app->make(WithdrawWikiInterface::class)->process($input, new WithdrawWikiOutput());
    }

    /**
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     */
    public function testProcessPrincipalNotFound(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $draftWiki = $this->createDraftWiki(ApprovalStatus::UnderReview, $principalIdentifier);
        $input = $this->createInput($draftWiki->wikiIdentifier(), $principalIdentifier);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')->once()->with($draftWiki->wikiIdentifier())->andReturn($draftWiki);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->with($principalIdentifier)->andReturn(null);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(WikiHistoryRepositoryInterface::class, Mockery::mock(WikiHistoryRepositoryInterface::class));
        $this->app->instance(WikiHistoryFactoryInterface::class, Mockery::mock(WikiHistoryFactoryInterface::class));

        $this->expectException(PrincipalNotFoundException::class);
        $this->app->make(WithdrawWikiInterface::class)->process($input, new WithdrawWikiOutput());
    }

    /**
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testProcessDisallowedByPolicy(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $draftWiki = $this->createDraftWiki(ApprovalStatus::UnderReview, $principalIdentifier);
        $input = $this->createInput($draftWiki->wikiIdentifier(), $principalIdentifier);

        $this->bindRepositoriesForPolicyResult($draftWiki, $principalIdentifier, false);

        $this->expectException(DisallowedException::class);
        $this->app->make(WithdrawWikiInterface::class)->process($input, new WithdrawWikiOutput());
    }

    /**
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessPendingIsInvalidStatus(): void
    {
        $this->assertInvalidStatus(ApprovalStatus::Pending);
    }

    /**
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessRejectedIsInvalidStatus(): void
    {
        $this->assertInvalidStatus(ApprovalStatus::Rejected);
    }

    /**
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessApprovedIsInvalidStatus(): void
    {
        $this->assertInvalidStatus(ApprovalStatus::Approved);
    }

    /**
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    private function assertInvalidStatus(ApprovalStatus $status): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $draftWiki = $this->createDraftWiki($status, $principalIdentifier);
        $input = $this->createInput($draftWiki->wikiIdentifier(), $principalIdentifier);

        $this->bindRepositoriesForPolicyResult($draftWiki, $principalIdentifier, true);

        $this->expectException(InvalidStatusException::class);
        $this->app->make(WithdrawWikiInterface::class)->process($input, new WithdrawWikiOutput());
    }

    private function bindRepositoriesForPolicyResult(
        DraftWiki $draftWiki,
        PrincipalIdentifier $principalIdentifier,
        bool $isAllowed,
        bool $expectSave = false,
        ?WikiHistory $history = null,
    ): void {
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')->once()->with($draftWiki->wikiIdentifier())->andReturn($draftWiki);
        if ($expectSave) {
            $draftWikiRepository->shouldReceive('save')->once()->with($draftWiki)->andReturn(null);
        } else {
            $draftWikiRepository->shouldNotReceive('save');
        }

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->with($principalIdentifier)->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->with(
                $principal,
                Action::WITHDRAW,
                Mockery::on(static fn (Resource $resource): bool => $resource->type() === $draftWiki->resourceType()
                    && $resource->editorId() === (string) $draftWiki->editorIdentifier()),
            )
            ->andReturn($isAllowed);

        $wikiHistoryFactory = Mockery::mock(WikiHistoryFactoryInterface::class);
        $wikiHistoryRepository = Mockery::mock(WikiHistoryRepositoryInterface::class);
        if ($history !== null) {
            $wikiHistoryFactory->shouldReceive('create')
                ->once()
                ->with(
                    HistoryActionType::DraftStatusChange,
                    $principalIdentifier,
                    $draftWiki->editorIdentifier(),
                    $draftWiki->publishedWikiIdentifier(),
                    Mockery::type(DraftWikiIdentifier::class),
                    ApprovalStatus::UnderReview,
                    ApprovalStatus::Pending,
                    null,
                    null,
                    $draftWiki->basic()->name(),
                )
                ->andReturn($history);
            $wikiHistoryRepository->shouldReceive('save')->once()->with($history)->andReturn(null);
        } else {
            $wikiHistoryFactory->shouldNotReceive('create');
            $wikiHistoryRepository->shouldNotReceive('save');
        }

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(WikiHistoryFactoryInterface::class, $wikiHistoryFactory);
        $this->app->instance(WikiHistoryRepositoryInterface::class, $wikiHistoryRepository);
    }

    private function createInput(DraftWikiIdentifier $wikiIdentifier, PrincipalIdentifier $principalIdentifier): WithdrawWikiInput
    {
        return new WithdrawWikiInput(
            $wikiIdentifier,
            $principalIdentifier,
            new WikiIdentifier(StrTestHelper::generateUuid()),
            [],
            [],
        );
    }

    private function createDraftWiki(ApprovalStatus $status, ?PrincipalIdentifier $editorIdentifier): DraftWiki
    {
        return new DraftWiki(
            new DraftWikiIdentifier(StrTestHelper::generateUuid()),
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('gr-twice-' . StrTestHelper::generateSmallAlphaStr(8)),
            Language::KOREAN,
            ResourceType::GROUP,
            new GroupBasic(
                name: new Name('TWICE'),
                normalizedName: 'twice',
                agencyIdentifier: null,
                groupType: null,
                status: null,
                generation: null,
                debutDate: null,
                disbandDate: null,
                fandomName: new FandomName('ONCE'),
                officialColors: [],
                emoji: new Emoji(''),
                representativeSymbol: new RepresentativeSymbol(''),
            ),
            new SectionContentCollection(),
            new HexColor('#FF5733'),
            $status,
            $editorIdentifier,
            editedAt: new DateTimeImmutable(),
        );
    }

    private function createHistory(
        DraftWiki $draftWiki,
        PrincipalIdentifier $actorIdentifier,
        ApprovalStatus $fromStatus,
        ApprovalStatus $toStatus,
    ): WikiHistory {
        return new WikiHistory(
            new WikiHistoryIdentifier(StrTestHelper::generateUuid()),
            HistoryActionType::DraftStatusChange,
            $actorIdentifier,
            $draftWiki->editorIdentifier(),
            $draftWiki->publishedWikiIdentifier(),
            new DraftWikiIdentifier((string) $draftWiki->wikiIdentifier()),
            $fromStatus,
            $toStatus,
            null,
            null,
            $draftWiki->basic()->name(),
            new DateTimeImmutable(),
        );
    }
}
