<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Query;

use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Image\Application\DTO\MaterialSearchContext;
use Source\Wiki\Image\Application\Service\FreeMaterialSearchServiceInterface;
use Source\Wiki\Image\Application\UseCase\Query\FreeMaterialSiteReadModel;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\RelatedWikiInput;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\SearchFreeMaterialsInput;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\SearchFreeMaterialsInterface;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\SearchFreeMaterialsOutput;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SearchFreeMaterialsTest extends TestCase
{
    /** @return iterable<string, array{?string, ?string, string}> */
    public static function titles(): iterable
    {
        yield 'edited title' => ['  Unsaved title  ', 'Display name', 'Unsaved title'];
        yield 'blank title' => ['  ', '  Display name  ', 'Display name'];
        yield 'missing title' => [null, 'Display name', 'Display name'];
        yield 'title only' => ['Unsaved title', null, 'Unsaved title'];
    }

    #[DataProvider('titles')]
    public function testAuthorizedSearchBuildsContextFromInput(?string $title, ?string $displayName, string $expectedTitle): void
    {
        $this->runSearch(true, true, $title, $displayName, $expectedTitle);
    }

    public function testRejectsUnidentifiableTargetBeforeExternalSearch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->runSearch(true, true, null, '  ', '');
    }

    public function testRejectsNonWikiResourceTypeBeforeExternalSearch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->runSearch(true, true, resourceType: ResourceType::IMAGE);
    }

    /** @return iterable<string, array{string, ResourceType, string}> */
    public static function invalidRelatedWikis(): iterable
    {
        yield 'blank title' => ['  ', ResourceType::GROUP, 'member_of'];
        yield 'non-Wiki resource type' => ['Edited group', ResourceType::IMAGE, 'member_of'];
        yield 'blank relation type' => ['Edited group', ResourceType::GROUP, '  '];
    }

    #[DataProvider('invalidRelatedWikis')]
    public function testRejectsInvalidRelatedWikiBeforeExternalSearch(string $title, ResourceType $resourceType, string $relationType): void
    {
        $relatedWiki = new RelatedWikiInput(new WikiIdentifier(StrTestHelper::generateUuid()), $title, $resourceType, $relationType);
        $this->expectException(InvalidArgumentException::class);
        $this->runSearch(true, true, relatedWiki: $relatedWiki, expectsSearch: false);
    }

    public function testAuthorizationPrecedesRelatedWikiValidation(): void
    {
        $relatedWiki = new RelatedWikiInput(new WikiIdentifier(StrTestHelper::generateUuid()), '', ResourceType::GROUP, 'member_of');
        $this->expectException(DisallowedException::class);
        $this->runSearch(false, true, relatedWiki: $relatedWiki);
    }

    public function testPermissionDenialDoesNotInvokeExternalSearch(): void
    {
        $this->expectException(DisallowedException::class);
        $this->runSearch(false, true);
    }

    public function testMissingPrincipalDoesNotInvokeExternalSearch(): void
    {
        $this->expectException(PrincipalNotFoundException::class);
        $this->runSearch(false, false);
    }

    private function runSearch(bool $allowed, bool $exists, ?string $title = 'Unsaved title', ?string $displayName = null, string $expectedTitle = 'Unsaved title', ResourceType $resourceType = ResourceType::TALENT, ?RelatedWikiInput $relatedWiki = null, bool $expectsSearch = true): void
    {
        $id = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($id, new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
        $relatedWikis = [$relatedWiki ?? new RelatedWikiInput(new WikiIdentifier(StrTestHelper::generateUuid()), 'Edited group', ResourceType::GROUP, 'member_of')];
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->with($id)->andReturn($exists ? $principal : null);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        if ($exists) {
            $policyEvaluator->shouldReceive('evaluate')->once()->with($principal, Action::SEARCH_FREE_MATERIALS, Mockery::on(static fn (Resource $resource): bool => $resource->type() === ResourceType::IMAGE))->andReturn($allowed);
        } else {
            $policyEvaluator->shouldNotReceive('evaluate');
        }
        $freeMaterialSearchService = Mockery::mock(FreeMaterialSearchServiceInterface::class);
        $site = new FreeMaterialSiteReadModel('Commons', 'https://commons.wikimedia.org/photo', 'Matches', 'Free with attribution', 'https://commons.wikimedia.org/license');
        if ($expectsSearch && $allowed && $exists && $expectedTitle !== '' && $resourceType !== ResourceType::IMAGE) {
            $freeMaterialSearchService->shouldReceive('search')->once()->with(Mockery::on(static fn (MaterialSearchContext $context): bool => $context->title === $expectedTitle
                && $context->resourceType === $resourceType
                && $context->language === Language::ENGLISH
                && count($context->relatedWikis) === count($relatedWikis)
                && $context->relatedWikis[0]->identifier === $relatedWikis[0]->identifier
                && $context->relatedWikis[0]->title === $relatedWikis[0]->title
                && $context->relatedWikis[0]->resourceType === $relatedWikis[0]->resourceType
                && $context->relatedWikis[0]->relationType === $relatedWikis[0]->relationType))->andReturn([$site]);
        } else {
            $freeMaterialSearchService->shouldNotReceive('search');
        }
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app()->instance(FreeMaterialSearchServiceInterface::class, $freeMaterialSearchService);
        $output = new SearchFreeMaterialsOutput();
        $this->app()->make(SearchFreeMaterialsInterface::class)->process(new SearchFreeMaterialsInput($id, $title, $displayName, $resourceType, Language::ENGLISH, $relatedWikis), $output);
        self::assertSame(['sites' => [$site->toArray()]], $output->toArray());
    }
}
