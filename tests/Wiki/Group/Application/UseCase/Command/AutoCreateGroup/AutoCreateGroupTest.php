<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\AutoCreateGroup;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Application\UseCase\Command\AutoCreateGroup\AutoCreateGroupInput;
use Source\Wiki\Group\Application\UseCase\Command\AutoCreateGroup\AutoCreateGroupInterface;
use Source\Wiki\Group\Application\UseCase\Command\AutoCreateGroup\GeneratedGroupData;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Group\Domain\Service\AutoGroupCreationServiceInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\AutoGroupCreationPayload;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\Service\SlugGeneratorServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AutoCreateGroupTest extends TestCase
{
    /**
     * 正常系: ActorがAdministratorの場合、正しく自動作成されること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     * @throws UnauthorizedException
     */
    public function testProcessWithAdministrator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $payload = $this->makePayload();
        $generatedData = $this->makeGeneratedGroupData();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutoGroupCreationServiceInterface::class);
        $service->shouldReceive('generate')
            ->once()
            ->with($payload)
            ->andReturn($generatedData);

        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generate')
            ->once()
            ->with('TWICE')
            ->andReturn(new Slug('twice'));

        $repository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoGroupCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftGroupRepositoryInterface::class, $repository);

        $input = new AutoCreateGroupInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutoCreateGroupInterface::class);

        $result = $useCase->process($input);

        $this->assertEquals((string) $payload->name(), (string) $result->name());
        $this->assertEquals('twice', (string) $result->slug());
        $this->assertEquals('auto generated description', (string) $result->description());
    }

    /**
     * 正常系: ActorがSenior Collaboratorの場合、正しく自動作成されること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     * @throws UnauthorizedException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $payload = $this->makePayload();
        $generatedData = $this->makeGeneratedGroupData();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutoGroupCreationServiceInterface::class);
        $service->shouldReceive('generate')
            ->once()
            ->with($payload)
            ->andReturn($generatedData);

        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generate')
            ->once()
            ->with('TWICE')
            ->andReturn(new Slug('twice'));

        $repository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoGroupCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftGroupRepositoryInterface::class, $repository);

        $input = new AutoCreateGroupInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutoCreateGroupInterface::class);

        $result = $useCase->process($input);

        $this->assertEquals((string) $payload->name(), (string) $result->name());
        $this->assertEquals('twice', (string) $result->slug());
    }

    /**
     * 異常系: ActorがAdministratorかSenior Collaboratorでない場合は、例外がスローされること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     * @throws UnauthorizedException
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

        $service = Mockery::mock(AutoGroupCreationServiceInterface::class);
        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $repository = Mockery::mock(DraftGroupRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoGroupCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftGroupRepositoryInterface::class, $repository);

        $input = new AutoCreateGroupInput($payload, $principalIdentifier);
        $this->setPolicyEvaluatorResult(false);
        $useCase = $this->app->make(AutoCreateGroupInterface::class);

        $this->expectException(UnauthorizedException::class);
        $useCase->process($input);
    }

    /**
     * 異常系: 指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @throws BindingResolutionException
     * @throws UnauthorizedException
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

        $service = Mockery::mock(AutoGroupCreationServiceInterface::class);
        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $repository = Mockery::mock(DraftGroupRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoGroupCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftGroupRepositoryInterface::class, $repository);

        $input = new AutoCreateGroupInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutoCreateGroupInterface::class);

        $this->expectException(PrincipalNotFoundException::class);
        $useCase->process($input);
    }

    /**
     * 正常系: API失敗時に空文字やNullが使用されること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalNotFoundException
     * @throws UnauthorizedException
     */
    public function testProcessWithEmptyGeneratedData(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $payload = $this->makePayload();
        $emptyGeneratedData = new GeneratedGroupData(
            alphabetName: null,
            description: null,
            sources: [],
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $service = Mockery::mock(AutoGroupCreationServiceInterface::class);
        $service->shouldReceive('generate')
            ->once()
            ->with($payload)
            ->andReturn($emptyGeneratedData);

        $slugGeneratorService = Mockery::mock(SlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generate')
            ->once()
            ->with('트와이스')
            ->andReturn(new Slug('twice'));

        $repository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(AutoGroupCreationServiceInterface::class, $service);
        $this->app->instance(SlugGeneratorServiceInterface::class, $slugGeneratorService);
        $this->app->instance(DraftGroupRepositoryInterface::class, $repository);

        $input = new AutoCreateGroupInput($payload, $principalIdentifier);
        $useCase = $this->app->make(AutoCreateGroupInterface::class);

        $result = $useCase->process($input);

        $this->assertSame('', (string) $result->description());
    }

    private function makePayload(): AutoGroupCreationPayload
    {
        return new AutoGroupCreationPayload(
            Language::KOREAN,
            new GroupName('트와이스'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
        );
    }

    private function makeGeneratedGroupData(): GeneratedGroupData
    {
        return new GeneratedGroupData(
            alphabetName: 'TWICE',
            description: 'auto generated description',
            sources: [],
        );
    }
}
