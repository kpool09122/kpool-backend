<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Infrastructure\Service;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Application\Service\OfficialResourceUpdaterInterface;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class OfficialResourceUpdaterTest extends TestCase
{
    /**
     * 正常系: 事務所が公式化されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testMarkOfficialAgency(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $wiki = $this->createWiki($wikiId, ResourceType::AGENCY);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with(Mockery::on(static fn (TranslationSetIdentifier $id): bool => (string) $id === $wikiId))
            ->andReturn([$wiki]);
        $wikiRepository->shouldReceive('save')
            ->once()
            ->with($wiki)
            ->andReturn([]);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::AGENCY, new TranslationSetIdentifier($wikiId), $owner);

        $this->assertTrue($wiki->isOfficial());
        $this->assertSame((string) $owner, (string) $wiki->ownerAccountIdentifier());
    }

    /**
     * 正常系: グループが公式化されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testMarkOfficialGroup(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $wiki = $this->createWiki($wikiId, ResourceType::GROUP);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with(Mockery::on(static fn (TranslationSetIdentifier $id): bool => (string) $id === $wikiId))
            ->andReturn([$wiki]);
        $wikiRepository->shouldReceive('save')
            ->once()
            ->with($wiki)
            ->andReturn([]);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::GROUP, new TranslationSetIdentifier($wikiId), $owner);

        $this->assertTrue($wiki->isOfficial());
        $this->assertSame((string) $owner, (string) $wiki->ownerAccountIdentifier());
    }

    /**
     * 正常系: タレントが公式化されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testMarkOfficialTalent(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $wiki = $this->createWiki($wikiId, ResourceType::TALENT);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with(Mockery::on(static fn (TranslationSetIdentifier $id): bool => (string) $id === $wikiId))
            ->andReturn([$wiki]);
        $wikiRepository->shouldReceive('save')
            ->once()
            ->with($wiki)
            ->andReturn([]);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::TALENT, new TranslationSetIdentifier($wikiId), $owner);

        $this->assertTrue($wiki->isOfficial());
        $this->assertSame((string) $owner, (string) $wiki->ownerAccountIdentifier());
    }

    /**
     * 正常系: 歌が公式化されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testMarkOfficialSong(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $wiki = $this->createWiki($wikiId, ResourceType::SONG);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with(Mockery::on(static fn (TranslationSetIdentifier $id): bool => (string) $id === $wikiId))
            ->andReturn([$wiki]);
        $wikiRepository->shouldReceive('save')
            ->once()
            ->with($wiki)
            ->andReturn([]);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::SONG, new TranslationSetIdentifier($wikiId), $owner);

        $this->assertTrue($wiki->isOfficial());
        $this->assertSame((string) $owner, (string) $wiki->ownerAccountIdentifier());
    }

    /**
     * 正常系: 同じ owner で公式化済みの場合は更新されないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testMarkOfficialWhenAlreadyOfficialDoesNothing(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $wiki = $this->createWiki($wikiId, ResourceType::GROUP, $owner);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with(Mockery::on(static fn (TranslationSetIdentifier $id): bool => (string) $id === $wikiId))
            ->andReturn([$wiki]);
        $wikiRepository->shouldReceive('save')->never();

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::GROUP, new TranslationSetIdentifier($wikiId), $owner);

        $this->assertTrue($wiki->isOfficial());
    }

    /**
     * 正常系: 別 owner で公式化済みの場合は owner が差し替えられること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testMarkOfficialWhenOwnedByAnotherAccountReplacesOwner(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $previousOwner = new AccountIdentifier(StrTestHelper::generateUuid());
        $wiki = $this->createWiki($wikiId, ResourceType::GROUP, $previousOwner);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with(Mockery::on(static fn (TranslationSetIdentifier $id): bool => (string) $id === $wikiId))
            ->andReturn([$wiki]);
        $wikiRepository->shouldReceive('save')
            ->once()
            ->with($wiki)
            ->andReturn([]);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::GROUP, new TranslationSetIdentifier($wikiId), $owner);

        $this->assertTrue($wiki->isOfficial());
        $this->assertSame((string) $owner, (string) $wiki->ownerAccountIdentifier());
    }

    /**
     * 正常系: 対象が存在しない場合は何もしないこと.
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function testMarkOfficialWhenNotFoundDoesNothing(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with(Mockery::on(static fn (TranslationSetIdentifier $id): bool => (string) $id === $wikiId))
            ->andReturn([]);
        $wikiRepository->shouldReceive('save')->never();

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::AGENCY, new TranslationSetIdentifier($wikiId), $owner);
    }

    /**
     * 正常系: タレントが存在しない場合は何もしないこと.
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function testMarkOfficialTalentWhenNotFoundDoesNothing(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with(Mockery::on(static fn (TranslationSetIdentifier $id): bool => (string) $id === $wikiId))
            ->andReturn([]);
        $wikiRepository->shouldReceive('save')->never();

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::TALENT, new TranslationSetIdentifier($wikiId), $owner);
    }

    /**
     * 正常系: 歌が既に公式化済みの場合は更新されないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testMarkOfficialSongWhenAlreadyOfficialDoesNothing(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $wiki = $this->createWiki($wikiId, ResourceType::SONG, $owner);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with(Mockery::on(static fn (TranslationSetIdentifier $id): bool => (string) $id === $wikiId))
            ->andReturn([$wiki]);
        $wikiRepository->shouldReceive('save')->never();

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::SONG, new TranslationSetIdentifier($wikiId), $owner);

        $this->assertTrue($wiki->isOfficial());
    }

    /**
     * 正常系: 指定 owner の公式 Wiki が公式解除されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testUnmarkOfficial(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $wiki = $this->createWiki($wikiId, ResourceType::GROUP, $owner);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with(Mockery::on(static fn (TranslationSetIdentifier $id): bool => (string) $id === $wikiId))
            ->andReturn([$wiki]);
        $wikiRepository->shouldReceive('save')
            ->once()
            ->with($wiki)
            ->andReturn([]);

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->unmarkOfficial(ResourceType::GROUP, new TranslationSetIdentifier($wikiId), $owner);

        $this->assertFalse($wiki->isOfficial());
        $this->assertNull($wiki->ownerAccountIdentifier());
    }

    /**
     * 正常系: resource type が一致しない場合は公式解除されないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testUnmarkOfficialWhenResourceTypeDoesNotMatchDoesNothing(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $wiki = $this->createWiki($wikiId, ResourceType::SONG, $owner);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with(Mockery::on(static fn (TranslationSetIdentifier $id): bool => (string) $id === $wikiId))
            ->andReturn([$wiki]);
        $wikiRepository->shouldReceive('save')->never();

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->unmarkOfficial(ResourceType::GROUP, new TranslationSetIdentifier($wikiId), $owner);

        $this->assertTrue($wiki->isOfficial());
        $this->assertSame((string) $owner, (string) $wiki->ownerAccountIdentifier());
    }

    /**
     * 正常系: owner が一致しない場合は公式解除されないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testUnmarkOfficialWhenOwnerDoesNotMatchDoesNothing(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $otherOwner = new AccountIdentifier(StrTestHelper::generateUuid());
        $wiki = $this->createWiki($wikiId, ResourceType::GROUP, $owner);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findByTranslationSetIdentifier')
            ->once()
            ->with(Mockery::on(static fn (TranslationSetIdentifier $id): bool => (string) $id === $wikiId))
            ->andReturn([$wiki]);
        $wikiRepository->shouldReceive('save')->never();

        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->unmarkOfficial(ResourceType::GROUP, new TranslationSetIdentifier($wikiId), $otherOwner);

        $this->assertTrue($wiki->isOfficial());
        $this->assertSame((string) $owner, (string) $wiki->ownerAccountIdentifier());
    }

    private function createWiki(string $wikiId, ResourceType $resourceType, ?AccountIdentifier $owner = null): Wiki
    {
        $basic = Mockery::mock(BasicInterface::class);

        return new Wiki(
            new WikiIdentifier($wikiId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('gr-test-slug'),
            Language::ENGLISH,
            $resourceType,
            $basic, /** @phpstan-ignore argument.type */
            new SectionContentCollection(),
            null,
            new Version(1),
            $owner,
        );
    }
}
