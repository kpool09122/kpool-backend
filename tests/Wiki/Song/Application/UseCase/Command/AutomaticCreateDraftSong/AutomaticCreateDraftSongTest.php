<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Service\SlugGeneratorServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong\AutomaticCreateDraftSongInput;
use Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong\AutomaticCreateDraftSongInterface;
use Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong\GeneratedSongData;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;
use Source\Wiki\Song\Domain\Service\AutomaticDraftSongCreationServiceInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AutomaticDraftSongCreationPayload;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AutomaticCreateDraftSongTest extends TestCase
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
        $generatedData = $this->makeGeneratedSongData();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutomaticDraftSongCreationServiceInterface::class);
        $service->shouldReceive('generate')
            ->once()
            ->with($payload)
            ->andReturn($generatedData);

        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generate')
            ->once()
            ->with('Dynamite')
            ->andReturn(new Slug('dynamite'));

        $repository = Mockery::mock(DraftSongRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftSongCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftSongRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftSongInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutomaticCreateDraftSongInterface::class);

        $result = $useCase->process($input);

        $this->assertEquals((string) $payload->name(), (string) $result->name());
        $this->assertEquals('dynamite', (string) $result->slug());
        $this->assertEquals('auto generated overview', (string) $result->overView());
        $this->assertEquals('RM, SUGA', (string) $result->lyricist());
        $this->assertEquals('David Stewart', (string) $result->composer());
        $this->assertEquals('2020-08-21', $result->releaseDate()->format('Y-m-d'));
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
        $generatedData = $this->makeGeneratedSongData();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutomaticDraftSongCreationServiceInterface::class);
        $service->shouldReceive('generate')
            ->once()
            ->with($payload)
            ->andReturn($generatedData);

        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generate')
            ->once()
            ->with('Dynamite')
            ->andReturn(new Slug('dynamite'));

        $repository = Mockery::mock(DraftSongRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftSongCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftSongRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftSongInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutomaticCreateDraftSongInterface::class);

        $result = $useCase->process($input);

        $this->assertEquals((string) $payload->name(), (string) $result->name());
        $this->assertEquals('dynamite', (string) $result->slug());
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

        $service = Mockery::mock(AutomaticDraftSongCreationServiceInterface::class);
        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $repository = Mockery::mock(DraftSongRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftSongCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftSongRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftSongInput($payload, $principalIdentifier);
        $this->setPolicyEvaluatorResult(false);
        $useCase = $this->app->make(AutomaticCreateDraftSongInterface::class);

        $this->expectException(DisallowedException::class);
        $useCase->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
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

        $service = Mockery::mock(AutomaticDraftSongCreationServiceInterface::class);
        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $repository = Mockery::mock(DraftSongRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftSongCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftSongRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftSongInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutomaticCreateDraftSongInterface::class);

        $this->expectException(PrincipalNotFoundException::class);
        $useCase->process($input);
    }

    /**
     * 正常系: API失敗時に空文字やNullが使用されること.
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
        $emptyGeneratedData = new GeneratedSongData(
            alphabetName: null,
            lyricist: null,
            composer: null,
            releaseDate: null,
            overview: null,
            sources: [],
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutomaticDraftSongCreationServiceInterface::class);
        $service->shouldReceive('generate')
            ->once()
            ->with($payload)
            ->andReturn($emptyGeneratedData);

        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generate')
            ->once()
            ->with('다이나마이트')
            ->andReturn(new Slug('test-song'));

        $repository = Mockery::mock(DraftSongRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutomaticDraftSongCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftSongRepositoryInterface::class, $repository);

        $input = new AutomaticCreateDraftSongInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutomaticCreateDraftSongInterface::class);

        $result = $useCase->process($input);

        $this->assertSame('', (string) $result->lyricist());
        $this->assertSame('', (string) $result->composer());
        $this->assertNull($result->releaseDate());
        $this->assertSame('', (string) $result->overView());
    }

    private function makePayload(): AutomaticDraftSongCreationPayload
    {
        return new AutomaticDraftSongCreationPayload(
            Language::KOREAN,
            new SongName('다이나마이트'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new TalentIdentifier(StrTestHelper::generateUuid()),
        );
    }

    private function makeGeneratedSongData(): GeneratedSongData
    {
        return new GeneratedSongData(
            alphabetName: 'Dynamite',
            lyricist: 'RM, SUGA',
            composer: 'David Stewart',
            releaseDate: '2020-08-21',
            overview: 'auto generated overview',
            sources: [],
        );
    }
}
