<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\AutoCreateTalent;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Service\SlugGeneratorServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Talent\Application\UseCase\Command\AutoCreateTalent\AutoCreateTalentInput;
use Source\Wiki\Talent\Application\UseCase\Command\AutoCreateTalent\AutoCreateTalentInterface;
use Source\Wiki\Talent\Application\UseCase\Command\AutoCreateTalent\GeneratedTalentData;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Service\AutoTalentCreationServiceInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AutoTalentCreationPayload;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AutoCreateTalentTest extends TestCase
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
        $generatedData = $this->makeGeneratedTalentData();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutoTalentCreationServiceInterface::class);
        $service->shouldReceive('generate')
            ->once()
            ->with($payload)
            ->andReturn($generatedData);

        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generate')
            ->once()
            ->with('Jimin')
            ->andReturn(new Slug('jimin'));

        $repository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoTalentCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftTalentRepositoryInterface::class, $repository);

        $input = new AutoCreateTalentInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutoCreateTalentInterface::class);

        $result = $useCase->process($input);

        $this->assertEquals((string) $payload->name(), (string) $result->name());
        $this->assertEquals('jimin', (string) $result->slug());
        $this->assertEquals('auto generated career', (string) $result->career());
        $this->assertEquals('박지민', (string) $result->realName());
        $this->assertEquals('1995-10-13', $result->birthday()->format('Y-m-d'));
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
        $generatedData = $this->makeGeneratedTalentData();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutoTalentCreationServiceInterface::class);
        $service->shouldReceive('generate')
            ->once()
            ->with($payload)
            ->andReturn($generatedData);

        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generate')
            ->once()
            ->with('Jimin')
            ->andReturn(new Slug('jimin'));

        $repository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoTalentCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftTalentRepositoryInterface::class, $repository);

        $input = new AutoCreateTalentInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutoCreateTalentInterface::class);

        $result = $useCase->process($input);

        $this->assertEquals((string) $payload->name(), (string) $result->name());
        $this->assertEquals('jimin', (string) $result->slug());
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

        $service = Mockery::mock(AutoTalentCreationServiceInterface::class);
        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $repository = Mockery::mock(DraftTalentRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoTalentCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftTalentRepositoryInterface::class, $repository);

        $input = new AutoCreateTalentInput($payload, $principalIdentifier);
        $this->setPolicyEvaluatorResult(false);
        $useCase = $this->app->make(AutoCreateTalentInterface::class);

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

        $service = Mockery::mock(AutoTalentCreationServiceInterface::class);
        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $repository = Mockery::mock(DraftTalentRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoTalentCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftTalentRepositoryInterface::class, $repository);

        $input = new AutoCreateTalentInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutoCreateTalentInterface::class);

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
        $emptyGeneratedData = new GeneratedTalentData(
            alphabetName: null,
            realName: null,
            birthday: null,
            description: null,
            sources: [],
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutoTalentCreationServiceInterface::class);
        $service->shouldReceive('generate')
            ->once()
            ->with($payload)
            ->andReturn($emptyGeneratedData);

        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generate')
            ->once()
            ->with('테스트탤런트')
            ->andReturn(new Slug('test-talent'));

        $repository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoTalentCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftTalentRepositoryInterface::class, $repository);

        $input = new AutoCreateTalentInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutoCreateTalentInterface::class);

        $result = $useCase->process($input);

        $this->assertSame('', (string) $result->realName());
        $this->assertNull($result->birthday());
        $this->assertSame('', (string) $result->career());
    }

    private function makePayload(): AutoTalentCreationPayload
    {
        return new AutoTalentCreationPayload(
            Language::KOREAN,
            new TalentName('테스트탤런트'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [
                new GroupIdentifier(StrTestHelper::generateUuid()),
            ],
        );
    }

    private function makeGeneratedTalentData(): GeneratedTalentData
    {
        return new GeneratedTalentData(
            alphabetName: 'Jimin',
            realName: '박지민',
            birthday: '1995-10-13',
            description: 'auto generated career',
            sources: [],
        );
    }
}
