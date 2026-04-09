<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Shared\Domain\Service\SlugGeneratorServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki\AutoCreateWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki\AutoCreateWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki\AutoCreateWikiOutput;
use Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki\GeneratedWikiData;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Service\AutoWikiCreationServiceInterface;
use Source\Wiki\Wiki\Domain\ValueObject\AutoWikiCreationPayload;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupStatus;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\Block\TextBlock;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AutoCreateWikiTest extends TestCase
{
    /**
     * 正常系: ActorがAdministratorの場合、正しく自動作成されること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     * @throws DisallowedException
     */
    public function testProcessWithAdministrator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $payload = $this->makePayload();
        $generatedData = $this->makeGeneratedWikiData();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutoWikiCreationServiceInterface::class);
        $service->shouldReceive('generate')
            ->once()
            ->with($payload)
            ->andReturn($generatedData);

        $normalizationService = Mockery::mock(NormalizationServiceInterface::class);
        $normalizationService->shouldReceive('normalize')
            ->once()
            ->with('트와이스', Language::KOREAN)
            ->andReturn('ㅌㅇㅇㅅ');

        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generate')
            ->once()
            ->with('TWICE')
            ->andReturn(new Slug('twice'));

        $repository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoWikiCreationServiceInterface::class, $service);
        $this->app->instance(NormalizationServiceInterface::class, $normalizationService);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftWikiRepositoryInterface::class, $repository);

        $input = new AutoCreateWikiInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutoCreateWikiInterface::class);

        $output = new AutoCreateWikiOutput();
        $useCase->process($input, $output);
        $result = $output->toArray();

        $this->assertSame((string) $payload->name(), $result['name']);
        $this->assertSame(ResourceType::GROUP->value, $result['resourceType']);
    }

    /**
     * 正常系: ActorがSenior Collaboratorの場合、正しく自動作成されること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     * @throws DisallowedException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $payload = $this->makePayload();
        $generatedData = $this->makeGeneratedWikiData();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutoWikiCreationServiceInterface::class);
        $service->shouldReceive('generate')
            ->once()
            ->with($payload)
            ->andReturn($generatedData);

        $normalizationService = Mockery::mock(NormalizationServiceInterface::class);
        $normalizationService->shouldReceive('normalize')
            ->once()
            ->with('트와이스', Language::KOREAN)
            ->andReturn('ㅌㅇㅇㅅ');

        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generate')
            ->once()
            ->with('TWICE')
            ->andReturn(new Slug('twice'));

        $repository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoWikiCreationServiceInterface::class, $service);
        $this->app->instance(NormalizationServiceInterface::class, $normalizationService);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftWikiRepositoryInterface::class, $repository);

        $input = new AutoCreateWikiInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutoCreateWikiInterface::class);

        $output = new AutoCreateWikiOutput();
        $useCase->process($input, $output);
        $result = $output->toArray();

        $this->assertSame((string) $payload->name(), $result['name']);
    }

    /**
     * 異常系: ActorがAdministratorかSenior Collaboratorでない場合は、例外がスローされること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     * @throws DisallowedException
     */
    public function testProcessWithUnauthorizedRole(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $payload = $this->makePayload();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutoWikiCreationServiceInterface::class);
        $normalizationService = Mockery::mock(NormalizationServiceInterface::class);
        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $repository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoWikiCreationServiceInterface::class, $service);
        $this->app->instance(NormalizationServiceInterface::class, $normalizationService);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftWikiRepositoryInterface::class, $repository);

        $input = new AutoCreateWikiInput($payload, $principalIdentifier);
        $this->setPolicyEvaluatorResult(false);
        $useCase = $this->app->make(AutoCreateWikiInterface::class);

        $this->expectException(DisallowedException::class);
        $useCase->process($input, new AutoCreateWikiOutput());
    }

    /**
     * 異常系: 指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @throws BindingResolutionException
     * @throws DisallowedException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $payload = $this->makePayload();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $service = Mockery::mock(AutoWikiCreationServiceInterface::class);
        $normalizationService = Mockery::mock(NormalizationServiceInterface::class);
        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $repository = Mockery::mock(DraftWikiRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoWikiCreationServiceInterface::class, $service);
        $this->app->instance(NormalizationServiceInterface::class, $normalizationService);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftWikiRepositoryInterface::class, $repository);

        $input = new AutoCreateWikiInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutoCreateWikiInterface::class);

        $this->expectException(PrincipalNotFoundException::class);
        $useCase->process($input, new AutoCreateWikiOutput());
    }

    /**
     * 正常系: API失敗時に空のセクションとデフォルトBasicが使用されること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     * @throws DisallowedException
     */
    public function testProcessWithEmptyGeneratedData(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $payload = $this->makePayload();
        $emptyGeneratedData = new GeneratedWikiData(
            alphabetName: null,
            basic: $this->makeBasic(),
            sections: new SectionContentCollection(),
            sources: [],
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutoWikiCreationServiceInterface::class);
        $service->shouldReceive('generate')
            ->once()
            ->with($payload)
            ->andReturn($emptyGeneratedData);

        $normalizationService = Mockery::mock(NormalizationServiceInterface::class);
        $normalizationService->shouldReceive('normalize')
            ->once()
            ->with('트와이스', Language::KOREAN)
            ->andReturn('ㅌㅇㅇㅅ');

        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generate')
            ->once()
            ->with('트와이스')
            ->andReturn(new Slug('twice'));

        $repository = Mockery::mock(DraftWikiRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoWikiCreationServiceInterface::class, $service);
        $this->app->instance(NormalizationServiceInterface::class, $normalizationService);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftWikiRepositoryInterface::class, $repository);

        $input = new AutoCreateWikiInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutoCreateWikiInterface::class);

        $output = new AutoCreateWikiOutput();
        $useCase->process($input, $output);
        $result = $output->toArray();

        $this->assertNotNull($result['language']);
    }

    private function makePayload(): AutoWikiCreationPayload
    {
        return new AutoWikiCreationPayload(
            Language::KOREAN,
            ResourceType::GROUP,
            new Name('트와이스'),
            new WikiIdentifier(StrTestHelper::generateUuid()),
            [],
            [],
        );
    }

    private function makeBasic(): GroupBasic
    {
        return new GroupBasic(
            name: new Name('트와이스'),
            normalizedName: 'twice',
            agencyIdentifier: null,
            groupType: null,
            status: null,
            generation: null,
            debutDate: null,
            disbandDate: null,
            fandomName: new FandomName(''),
            officialColors: [],
            emoji: new Emoji(''),
            representativeSymbol: new RepresentativeSymbol(''),
            mainImageIdentifier: null,
        );
    }

    private function makeGeneratedWikiData(): GeneratedWikiData
    {
        $basic = new GroupBasic(
            name: new Name('트와이스'),
            normalizedName: 'twice',
            agencyIdentifier: null,
            groupType: GroupType::GIRL_GROUP,
            status: GroupStatus::ACTIVE,
            generation: null,
            debutDate: null,
            disbandDate: null,
            fandomName: new FandomName('ONCE'),
            officialColors: [],
            emoji: new Emoji(''),
            representativeSymbol: new RepresentativeSymbol(''),
            mainImageIdentifier: null,
        );

        $sections = new SectionContentCollection([
            new TextBlock(1, 'auto generated content'),
        ]);

        return new GeneratedWikiData(
            alphabetName: 'TWICE',
            basic: $basic,
            sections: $sections,
            sources: [],
        );
    }
}
