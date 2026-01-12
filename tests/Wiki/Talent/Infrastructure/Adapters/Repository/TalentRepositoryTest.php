<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use JsonException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\CreateGroup;
use Tests\Helper\CreateTalent;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TalentRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDのタレント情報が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $id = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();
        $translation = Language::KOREAN;
        $name = '방찬';
        $realName = '크리스토퍼 방';
        $agencyId = StrTestHelper::generateUuid();
        $groupIdentifiers = [StrTestHelper::generateUuid(), StrTestHelper::generateUuid()];
        $birthday = '1997-10-03';
        $career = 'Stray Kids leader, producer, and rapper. Member of 3RACHA.';
        $imageLink = '/images/talents/bangchan.jpg';
        $relevantVideoLinks = ['https://www.youtube.com/watch?v=EaswWiwMVs8', 'https://www.youtube.com/watch?v=dcNRbbQBJUE'];
        $version = 3;

        // 先にグループを作成
        foreach ($groupIdentifiers as $groupId) {
            CreateGroup::create($groupId);
        }

        CreateTalent::create($id, [
            'translation_set_identifier' => $translationSetId,
            'language' => $translation->value,
            'name' => $name,
            'real_name' => $realName,
            'agency_id' => $agencyId,
            'group_identifiers' => $groupIdentifiers,
            'birthday' => $birthday,
            'career' => $career,
            'image_link' => $imageLink,
            'relevant_video_links' => $relevantVideoLinks,
            'version' => $version,
        ]);

        $repository = $this->app->make(TalentRepositoryInterface::class);
        $talent = $repository->findById(new TalentIdentifier($id));

        $this->assertInstanceOf(Talent::class, $talent);
        $this->assertSame($id, (string) $talent->talentIdentifier());
        $this->assertSame($translationSetId, (string) $talent->translationSetIdentifier());
        $this->assertSame($translation, $talent->language());
        $this->assertSame($name, (string) $talent->name());
        $this->assertSame($realName, (string) $talent->realName());
        $this->assertSame($agencyId, (string) $talent->agencyIdentifier());
        $this->assertSame($groupIdentifiers, array_map(
            static fn (GroupIdentifier $identifier): string => (string) $identifier,
            $talent->groupIdentifiers(),
        ));
        $this->assertInstanceOf(Birthday::class, $talent->birthday());
        $this->assertSame($birthday, $talent->birthday()->format('Y-m-d'));
        $this->assertSame($career, (string) $talent->career());
        $this->assertSame($imageLink, (string) $talent->imageLink());
        $this->assertSame($relevantVideoLinks, $talent->relevantVideoLinks()->toStringArray());
        $this->assertSame($version, $talent->version()->value());
    }

    /**
     * 正常系：誕生日が未設定の場合はnullが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenBirthdayIsNull(): void
    {
        $id = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();

        CreateGroup::create($groupId);
        CreateTalent::create($id, [
            'language' => Language::KOREAN->value,
            'name' => '리노',
            'real_name' => '이민호',
            'agency_id' => StrTestHelper::generateUuid(),
            'group_identifiers' => [$groupId],
            'birthday' => null,
            'career' => 'Stray Kids main dancer and sub-vocalist.',
            'image_link' => '/images/talents/leeknow.jpg',
        ]);

        $repository = $this->app->make(TalentRepositoryInterface::class);
        $talent = $repository->findById(new TalentIdentifier($id));

        $this->assertInstanceOf(Talent::class, $talent);
        $this->assertNull($talent->birthday());
    }

    /**
     * 正常系：存在しないIDを指定した場合、nullが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNoTalent(): void
    {
        $repository = $this->app->make(TalentRepositoryInterface::class);
        $talent = $repository->findById(new TalentIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($talent);
    }

    /**
     * 正常系：タレント情報を保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws JsonException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $groupId1 = StrTestHelper::generateUuid();
        $groupId2 = StrTestHelper::generateUuid();

        // 先にグループを作成
        CreateGroup::create($groupId1);
        CreateGroup::create($groupId2);

        $talent = new Talent(
            new TalentIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new TalentName('한'),
            new RealName('지성'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [
                new GroupIdentifier($groupId1),
                new GroupIdentifier($groupId2),
            ],
            new Birthday(new DateTimeImmutable('2000-09-14')),
            new Career('Stray Kids lead vocalist and main rapper. Member of 3RACHA.'),
            new ImagePath('/images/talents/han.jpg'),
            new RelevantVideoLinks([
                new ExternalContentLink('https://www.youtube.com/watch?v=EaswWiwMVs8'),
                new ExternalContentLink('https://www.youtube.com/watch?v=dcNRbbQBJUE'),
            ]),
            new Version(4),
        );

        $repository = $this->app->make(TalentRepositoryInterface::class);
        $repository->save($talent);

        $this->assertDatabaseHas('talents', [
            'id' => (string) $talent->talentIdentifier(),
            'translation_set_identifier' => (string) $talent->translationSetIdentifier(),
            'language' => $talent->language()->value,
            'name' => (string) $talent->name(),
            'real_name' => (string) $talent->realName(),
            'agency_id' => (string) $talent->agencyIdentifier(),
            'birthday' => $talent->birthday()?->format('Y-m-d'),
            'career' => (string) $talent->career(),
            'image_link' => (string) $talent->imageLink(),
            'version' => $talent->version()->value(),
        ]);

        // 中間テーブルの確認
        $this->assertDatabaseHas('talent_group', [
            'talent_id' => (string) $talent->talentIdentifier(),
            'group_id' => $groupId1,
        ]);
        $this->assertDatabaseHas('talent_group', [
            'talent_id' => (string) $talent->talentIdentifier(),
            'group_id' => $groupId2,
        ]);

        $rawVideos = DB::table('talents')
            ->where('id', (string) $talent->talentIdentifier())
            ->value('relevant_video_links');

        $decodedVideos = json_decode((string) $rawVideos, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(
            $talent->relevantVideoLinks()->toStringArray(),
            $decodedVideos,
        );
    }

    /**
     * 正常系：指定したTranslationSetIdentifierに紐づくTalent一覧が取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifier(): void
    {
        $translationSetId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();

        CreateGroup::create($groupId);

        // 同じtranslationSetIdentifierを持つTalentを2つ作成
        $talentId1 = StrTestHelper::generateUuid();
        CreateTalent::create($talentId1, [
            'translation_set_identifier' => $translationSetId,
            'language' => Language::KOREAN->value,
            'name' => '채영',
            'real_name' => '손채영',
            'agency_id' => StrTestHelper::generateUuid(),
            'group_identifiers' => [$groupId],
            'birthday' => '1999-04-23',
            'career' => 'Test career',
            'image_link' => '/images/test.jpg',
            'version' => 1,
        ]);

        $talentId2 = StrTestHelper::generateUuid();
        CreateTalent::create($talentId2, [
            'translation_set_identifier' => $translationSetId,
            'language' => Language::ENGLISH->value,
            'name' => 'Chaeyoung',
            'real_name' => 'Son Chaeyoung',
            'agency_id' => StrTestHelper::generateUuid(),
            'group_identifiers' => [$groupId],
            'birthday' => '1999-04-23',
            'career' => 'Test career en',
            'image_link' => '/images/test.jpg',
            'version' => 1,
        ]);

        // 別のtranslationSetIdentifierを持つTalent（取得されないはず）
        $talentId3 = StrTestHelper::generateUuid();
        CreateTalent::create($talentId3, [
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'language' => Language::KOREAN->value,
            'name' => '지효',
            'real_name' => '박지효',
            'version' => 1,
        ]);

        $repository = $this->app->make(TalentRepositoryInterface::class);
        $talents = $repository->findByTranslationSetIdentifier(
            new TranslationSetIdentifier($translationSetId)
        );

        $this->assertCount(2, $talents);
        $talentIds = array_map(
            static fn (Talent $talent): string => (string) $talent->talentIdentifier(),
            $talents
        );
        $this->assertContains($talentId1, $talentIds);
        $this->assertContains($talentId2, $talentIds);
    }

    /**
     * 正常系：指定したTranslationSetIdentifierにTalentが存在しない場合、空配列が返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifierWhenNoTalents(): void
    {
        $repository = $this->app->make(TalentRepositoryInterface::class);
        $talents = $repository->findByTranslationSetIdentifier(
            new TranslationSetIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertIsArray($talents);
        $this->assertEmpty($talents);
    }

    /**
     * 正常系：指定したOwnerAccountIdに紐づく公式Talentが取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByOwnerAccountId(): void
    {
        $talentId = StrTestHelper::generateUuid();
        $ownerAccountId = StrTestHelper::generateUuid();

        CreateTalent::create($talentId, [
            'name' => '公式タレント',
            'real_name' => '本名',
            'is_official' => true,
            'owner_account_id' => $ownerAccountId,
            'version' => 1,
        ]);

        $repository = $this->app->make(TalentRepositoryInterface::class);
        $talent = $repository->findByOwnerAccountId(new AccountIdentifier($ownerAccountId));

        $this->assertInstanceOf(Talent::class, $talent);
        $this->assertSame($talentId, (string) $talent->talentIdentifier());
        $this->assertTrue($talent->isOfficial());
        $this->assertSame($ownerAccountId, (string) $talent->ownerAccountIdentifier());
    }

    /**
     * 正常系：指定したOwnerAccountIdに紐づくTalentが存在しない場合、nullが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByOwnerAccountIdWhenNotFound(): void
    {
        $repository = $this->app->make(TalentRepositoryInterface::class);
        $talent = $repository->findByOwnerAccountId(
            new AccountIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertNull($talent);
    }

    /**
     * 正常系：指定したOwnerAccountIdに紐づくTalentが非公式の場合、nullが返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByOwnerAccountIdWhenNotOfficial(): void
    {
        $talentId = StrTestHelper::generateUuid();
        $ownerAccountId = StrTestHelper::generateUuid();

        CreateTalent::create($talentId, [
            'name' => '非公式タレント',
            'real_name' => '本名',
            'is_official' => false,
            'owner_account_id' => $ownerAccountId,
            'version' => 1,
        ]);

        $repository = $this->app->make(TalentRepositoryInterface::class);
        $talent = $repository->findByOwnerAccountId(new AccountIdentifier($ownerAccountId));

        $this->assertNull($talent);
    }
}
