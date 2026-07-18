<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\DeleteWiki;

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
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Command\DeleteWiki\DeleteWiki;
use Source\Wiki\Wiki\Application\UseCase\Command\DeleteWiki\DeleteWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\DeleteWiki\DeleteWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\DeleteWiki\DeleteWikiOutput;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\HexColor;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DeleteWikiTest extends TestCase
{
    public function test__construct(): void
    {
        $this->app->instance(DraftWikiRepositoryInterface::class, Mockery::mock(DraftWikiRepositoryInterface::class));
        $this->app->instance(PrincipalRepositoryInterface::class, Mockery::mock(PrincipalRepositoryInterface::class));

        $deleteWiki = $this->app->make(DeleteWikiInterface::class);

        $this->assertInstanceOf(DeleteWiki::class, $deleteWiki);
    }

    /**
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessDeletesPendingDraftWiki(): void
    {
        $this->assertProcessDeletesDraftWiki(ApprovalStatus::Pending);
    }

    /**
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessDeletesRejectedDraftWiki(): void
    {
        $this->assertProcessDeletesDraftWiki(ApprovalStatus::Rejected);
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

        $this->expectException(WikiNotFoundException::class);
        $this->app->make(DeleteWikiInterface::class)->process($input, new DeleteWikiOutput());
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
        $draftWiki = $this->createDraftWiki(ApprovalStatus::Pending, $principalIdentifier);
        $input = $this->createInput($draftWiki->wikiIdentifier(), $principalIdentifier);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')->once()->with($draftWiki->wikiIdentifier())->andReturn($draftWiki);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->with($principalIdentifier)->andReturn(null);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $this->app->make(DeleteWikiInterface::class)->process($input, new DeleteWikiOutput());
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
        $draftWiki = $this->createDraftWiki(ApprovalStatus::Pending, $principalIdentifier);
        $input = $this->createInput($draftWiki->wikiIdentifier(), $principalIdentifier);

        $this->bindRepositoriesForPolicyResult($draftWiki, $principalIdentifier, false);

        $this->expectException(DisallowedException::class);
        $this->app->make(DeleteWikiInterface::class)->process($input, new DeleteWikiOutput());
    }

    /**
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testProcessDifferentEditorIsDisallowedByPolicyCondition(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $draftWiki = $this->createDraftWiki(ApprovalStatus::Pending, $editorIdentifier);
        $input = $this->createInput($draftWiki->wikiIdentifier(), $principalIdentifier);

        $this->bindRepositoriesForPolicyResult($draftWiki, $principalIdentifier, false);

        $this->expectException(DisallowedException::class);
        $this->app->make(DeleteWikiInterface::class)->process($input, new DeleteWikiOutput());
    }

    /**
     * @throws BindingResolutionException
     * @throws WikiNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessUnderReviewIsInvalidStatus(): void
    {
        $this->assertInvalidStatus(ApprovalStatus::UnderReview);
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
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    private function assertProcessDeletesDraftWiki(ApprovalStatus $status): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $draftWiki = $this->createDraftWiki($status, $principalIdentifier);
        $input = $this->createInput($draftWiki->wikiIdentifier(), $principalIdentifier);

        $this->bindRepositoriesForPolicyResult($draftWiki, $principalIdentifier, true, true);

        $this->app->make(DeleteWikiInterface::class)->process($input, new DeleteWikiOutput());
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
        $this->app->make(DeleteWikiInterface::class)->process($input, new DeleteWikiOutput());
    }

    private function bindRepositoriesForPolicyResult(
        DraftWiki $draftWiki,
        PrincipalIdentifier $principalIdentifier,
        bool $isAllowed,
        bool $expectDelete = false,
    ): void {
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $draftWikiRepository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $draftWikiRepository->shouldReceive('findById')->once()->with($draftWiki->wikiIdentifier())->andReturn($draftWiki);
        if ($expectDelete) {
            $draftWikiRepository->shouldReceive('delete')->once()->with($draftWiki)->andReturn(null);
        } else {
            $draftWikiRepository->shouldNotReceive('delete');
        }

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->with($principalIdentifier)->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->with(
                $principal,
                Action::DELETE,
                Mockery::on(static fn (Resource $resource): bool => $resource->type() === $draftWiki->resourceType()
                    && $resource->editorId() === (string) $draftWiki->editorIdentifier()),
            )
            ->andReturn($isAllowed);

        $this->app->instance(DraftWikiRepositoryInterface::class, $draftWikiRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
    }

    private function createInput(DraftWikiIdentifier $wikiIdentifier, PrincipalIdentifier $principalIdentifier): DeleteWikiInput
    {
        return new DeleteWikiInput(
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
}
