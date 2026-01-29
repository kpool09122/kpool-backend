<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\AutoCreateAgency;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Application\UseCase\Command\AutoCreateAgency\AutoCreateAgencyInput;
use Source\Wiki\Agency\Application\UseCase\Command\AutoCreateAgency\AutoCreateAgencyInterface;
use Source\Wiki\Agency\Application\UseCase\Command\AutoCreateAgency\GeneratedAgencyData;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Service\AutoAgencyCreationServiceInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\AutoAgencyCreationPayload;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Service\SlugGeneratorServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AutoCreateAgencyTest extends TestCase
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
        $generatedData = $this->makeGeneratedAgencyData();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutoAgencyCreationServiceInterface::class);
        $service->shouldReceive('generate')
            ->once()
            ->with($payload)
            ->andReturn($generatedData);

        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generate')
            ->once()
            ->with('JYP Entertainment')
            ->andReturn(new Slug('jyp-entertainment'));

        $repository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoAgencyCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $repository);

        $input = new AutoCreateAgencyInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutoCreateAgencyInterface::class);

        $result = $useCase->process($input);

        $this->assertEquals((string) $payload->name(), (string) $result->name());
        $this->assertEquals('jyp-entertainment', (string) $result->slug());
        $this->assertEquals('J.Y. Park', (string) $result->CEO());
        $this->assertEquals('auto generated description', (string) $result->description());
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
        $generatedData = $this->makeGeneratedAgencyData();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutoAgencyCreationServiceInterface::class);
        $service->shouldReceive('generate')
            ->once()
            ->with($payload)
            ->andReturn($generatedData);

        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generate')
            ->once()
            ->with('JYP Entertainment')
            ->andReturn(new Slug('jyp-entertainment'));

        $repository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoAgencyCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $repository);

        $input = new AutoCreateAgencyInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutoCreateAgencyInterface::class);

        $result = $useCase->process($input);

        $this->assertEquals((string) $payload->name(), (string) $result->name());
        $this->assertEquals('jyp-entertainment', (string) $result->slug());
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
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

        $service = Mockery::mock(AutoAgencyCreationServiceInterface::class);
        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $repository = Mockery::mock(DraftAgencyRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoAgencyCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $repository);

        $input = new AutoCreateAgencyInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutoCreateAgencyInterface::class);

        $this->expectException(PrincipalNotFoundException::class);
        $useCase->process($input);
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

        $service = Mockery::mock(AutoAgencyCreationServiceInterface::class);
        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $repository = Mockery::mock(DraftAgencyRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoAgencyCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $repository);

        $input = new AutoCreateAgencyInput($payload, $principalIdentifier);
        $this->setPolicyEvaluatorResult(false);
        $useCase = $this->app->make(AutoCreateAgencyInterface::class);

        $this->expectException(DisallowedException::class);
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
        $emptyGeneratedData = new GeneratedAgencyData(
            alphabetName: null,
            ceoName: null,
            foundedYear: null,
            description: null,
            sources: [],
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutoAgencyCreationServiceInterface::class);
        $service->shouldReceive('generate')
            ->once()
            ->with($payload)
            ->andReturn($emptyGeneratedData);

        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generate')
            ->once()
            ->with('JYP엔터테인먼트')
            ->andReturn(new Slug('jyp-entertainment'));

        $repository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoAgencyCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $repository);

        $input = new AutoCreateAgencyInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutoCreateAgencyInterface::class);

        $result = $useCase->process($input);

        $this->assertSame('', (string) $result->CEO());
        $this->assertSame('', (string) $result->description());
        $this->assertNull($result->foundedIn());
    }

    private function makePayload(): AutoAgencyCreationPayload
    {
        return new AutoAgencyCreationPayload(
            Language::KOREAN,
            new AgencyName('JYP엔터테인먼트'),
        );
    }

    private function makeGeneratedAgencyData(): GeneratedAgencyData
    {
        return new GeneratedAgencyData(
            alphabetName: 'JYP Entertainment',
            ceoName: 'J.Y. Park',
            foundedYear: 1997,
            description: 'auto generated description',
            sources: [],
        );
    }
}
