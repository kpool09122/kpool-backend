<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Infrastructure\Service;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description as AgencyDescription;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\Description as GroupDescription;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\OfficialCertification\Application\Service\OfficialResourceUpdaterInterface;
use Source\Wiki\OfficialCertification\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
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
        $agencyId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $agency = $this->createAgency($agencyId);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(static fn (AgencyIdentifier $id): bool => (string) $id === $agencyId))
            ->andReturn($agency);
        $agencyRepository->shouldReceive('save')
            ->once()
            ->with($agency)
            ->andReturnNull();

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $songRepository = Mockery::mock(SongRepositoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::AGENCY, new ResourceIdentifier($agencyId), $owner);

        $this->assertTrue($agency->isOfficial());
        $this->assertSame((string) $owner, (string) $agency->ownerAccountIdentifier());
    }

    /**
     * 正常系: グループが公式化されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testMarkOfficialGroup(): void
    {
        $groupId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $group = $this->createGroup($groupId);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(static fn (GroupIdentifier $id): bool => (string) $id === $groupId))
            ->andReturn($group);
        $groupRepository->shouldReceive('save')
            ->once()
            ->with($group)
            ->andReturnNull();

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $songRepository = Mockery::mock(SongRepositoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::GROUP, new ResourceIdentifier($groupId), $owner);

        $this->assertTrue($group->isOfficial());
        $this->assertSame((string) $owner, (string) $group->ownerAccountIdentifier());
    }

    /**
     * 正常系: タレントが公式化されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testMarkOfficialTalent(): void
    {
        $talentId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $talent = $this->createTalent($talentId);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(static fn (TalentIdentifier $id): bool => (string) $id === $talentId))
            ->andReturn($talent);
        $talentRepository->shouldReceive('save')
            ->once()
            ->with($talent)
            ->andReturnNull();

        $songRepository = Mockery::mock(SongRepositoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::TALENT, new ResourceIdentifier($talentId), $owner);

        $this->assertTrue($talent->isOfficial());
        $this->assertSame((string) $owner, (string) $talent->ownerAccountIdentifier());
    }

    /**
     * 正常系: 歌が公式化されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testMarkOfficialSong(): void
    {
        $songId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $song = $this->createSong($songId);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(static fn (SongIdentifier $id): bool => (string) $id === $songId))
            ->andReturn($song);
        $songRepository->shouldReceive('save')
            ->once()
            ->with($song)
            ->andReturnNull();

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::SONG, new ResourceIdentifier($songId), $owner);

        $this->assertTrue($song->isOfficial());
        $this->assertSame((string) $owner, (string) $song->ownerAccountIdentifier());
    }

    /**
     * 正常系: 既に公式化済みの場合は更新されないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testMarkOfficialWhenAlreadyOfficialDoesNothing(): void
    {
        $groupId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $group = $this->createGroup($groupId);
        $group->markOfficial($owner);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(static fn (GroupIdentifier $id): bool => (string) $id === $groupId))
            ->andReturn($group);
        $groupRepository->shouldReceive('save')->never();

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $songRepository = Mockery::mock(SongRepositoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::GROUP, new ResourceIdentifier($groupId), $owner);

        $this->assertTrue($group->isOfficial());
    }

    /**
     * 正常系: 対象が存在しない場合は何もしないこと.
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function testMarkOfficialWhenNotFoundDoesNothing(): void
    {
        $agencyId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(static fn (AgencyIdentifier $id): bool => (string) $id === $agencyId))
            ->andReturnNull();
        $agencyRepository->shouldReceive('save')->never();

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $songRepository = Mockery::mock(SongRepositoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::AGENCY, new ResourceIdentifier($agencyId), $owner);
    }

    /**
     * 正常系: タレントが存在しない場合は何もしないこと.
     */
    public function testMarkOfficialTalentWhenNotFoundDoesNothing(): void
    {
        $talentId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn (TalentIdentifier $id): bool => (string) $id === $talentId))
            ->andReturnNull();
        $talentRepository->shouldReceive('save')->never();
        $songRepository = Mockery::mock(SongRepositoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::TALENT, new ResourceIdentifier($talentId), $owner);
    }

    /**
     * 正常系: 歌が既に公式化済みの場合は更新されないこと.
     */
    public function testMarkOfficialSongWhenAlreadyOfficialDoesNothing(): void
    {
        $songId = StrTestHelper::generateUuid();
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $song = $this->createSong($songId);
        $song->markOfficial($owner);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn (SongIdentifier $id): bool => (string) $id === $songId))
            ->andReturn($song);
        $songRepository->shouldReceive('save')->never();

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $service = $this->app->make(OfficialResourceUpdaterInterface::class);

        $service->markOfficial(ResourceType::SONG, new ResourceIdentifier($songId), $owner);

        $this->assertTrue($song->isOfficial());
    }

    private function createAgency(string $agencyId): Agency
    {
        return new Agency(
            new AgencyIdentifier($agencyId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            Language::ENGLISH,
            new AgencyName('Agency'),
            'agency',
            new CEO('CEO'),
            'ceo',
            null,
            new AgencyDescription('Description'),
            new Version(1),
        );
    }

    private function createGroup(string $groupId): Group
    {
        return new Group(
            new GroupIdentifier($groupId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            Language::ENGLISH,
            new GroupName('Group'),
            'group',
            null,
            new GroupDescription('Description'),
            null,
            new Version(1),
        );
    }

    private function createTalent(string $talentId): Talent
    {
        return new Talent(
            new TalentIdentifier($talentId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            Language::ENGLISH,
            new TalentName('Talent'),
            'talent',
            new RealName('Real Name'),
            'real name',
            null,
            [],
            null,
            new Career('Career'),
            null,
            RelevantVideoLinks::formStringArray([]),
            new Version(1),
        );
    }

    private function createSong(string $songId): Song
    {
        return new Song(
            new SongIdentifier($songId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            Language::ENGLISH,
            new SongName('Song'),
            'song',
            null,
            null,
            null,
            new Lyricist('Lyricist'),
            'lyricist',
            new Composer('Composer'),
            'composer',
            null,
            new Overview('Overview'),
            null,
            null,
            new Version(1),
        );
    }
}
