<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Application\UseCase\Command\TranslateMember;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Member\Application\Exception\MemberNotFoundException;
use Source\Wiki\Member\Application\Service\TranslationServiceInterface;
use Source\Wiki\Member\Application\UseCase\Command\TranslateMember\TranslateMember;
use Source\Wiki\Member\Application\UseCase\Command\TranslateMember\TranslateMemberInput;
use Source\Wiki\Member\Application\UseCase\Command\TranslateMember\TranslateMemberInterface;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Entity\Member;
use Source\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Member\Domain\Repository\MemberRepositoryInterface;
use Source\Wiki\Member\Domain\ValueObject\Birthday;
use Source\Wiki\Member\Domain\ValueObject\Career;
use Source\Wiki\Member\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Member\Domain\ValueObject\MemberName;
use Source\Wiki\Member\Domain\ValueObject\RealName;
use Source\Wiki\Member\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateMemberTest extends TestCase
{
    /**
     * 正常系：DIが正しく動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $memberRepository = Mockery::mock(MemberRepositoryInterface::class);
        $this->app->instance(MemberRepositoryInterface::class, $memberRepository);
        $translateMember = $this->app->make(TranslateMemberInterface::class);
        $this->assertInstanceOf(TranslateMember::class, $translateMember);
    }

    /**
     * 正常系：正しく他の言語に翻訳されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws MemberNotFoundException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testProcess(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new MemberName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1999-04-23'));
        $career = new Career('손채영은 대한민국의 걸그룹 트와이스의 멤버입니다. 트와이스에서 메인래퍼와 서브보컬을 담당하고 있으며, 작사, 작곡에도 참여하며 다재다능한 아티스트로서의 면모를 보여주고 있습니다.
**데뷔 전**
어린 시절부터 춤에 재능을 보였던 채영은 2012년 JYP 엔터테인먼트 오디션에 합격하여 연습생 생활을 시작했습니다. 약 3년간의 연습생 기간을 거치며 랩과 보컬 실력을 갈고닦았고, 데뷔 전 GOT7의 "하지하지마" 뮤직비디오에 출연하기도 했습니다.
**SIXTEEN과 트와이스 데뷔**
2015년, JYP의 신인 걸그룹 트와이스의 멤버를 선발하는 서바이벌 프로그램 Mnet \'SIXTEEN\'에 참가하여 개성 있는 랩과 무대 매너로 주목을 받았습니다. 최종 멤버로 발탁되어 2015년 10월 20일, 트와이스의 첫 번째 미니 앨범 "THE STORY BEGINS"로 정식 데뷔했습니다.
**트와이스 활동 및 솔로 활동**
트와이스의 멤버로서 채영은 수많은 히트곡에 참여하며 전 세계적인 인기를 얻는 데 기여했습니다. 그룹 내에서 독특한 음색과 안정적인 랩 실력으로 곡의 매력을 더하고 있습니다.
또한, 다수의 트와이스 앨범 수록곡 작사에 참여하며 꾸준히 음악적 역량을 키워왔습니다. "PAGE TWO" 앨범의 "소중한 사랑" 랩 메이킹을 시작으로, "LIKEY", "What is Love?", "Feel Special" 등 다수의 곡 작업에 이름을 올렸습니다.
최근에는 솔로 아티스트로서의 활동도 시작하며 음악적 스펙트럼을 넓혀가고 있습니다.');
        $imagePath = new ImagePath('/resources/public/images/before.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks = [$link1, $link2, $link3];
        $relevantVideoLinks = new RelevantVideoLinks($externalContentLinks);

        $input = new TranslateMemberInput(
            $memberIdentifier,
        );

        $member = new Member(
            $memberIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
        );

        $jaMemberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $japanese = Translation::JAPANESE;
        $jaName = new MemberName('チェヨン');
        $jaRealName = new RealName('ソン・チェヨン');
        $jaGroupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $jaBirthday = new Birthday(new DateTimeImmutable('1999-04-23'));
        $jaCareer = new Career('ソン・チェヨンは、韓国のガールズグループTWICEのメンバーです。TWICEではメインラッパーとサブボーカルを担当しており、作詞・作曲にも参加し、多才なアーティストとしての一面を見せています。
**デビュー前**
幼い頃からダンスに才能を見せていたチェヨンは、2012年にJYPエンターテインメントのオーディションに合格し、練習生生活を始めました。約3年間の練習生期間を経てラップとボーカルの実力を磨き、デビュー前にはGOT7の「Stop stop it」のミュージックビデオに出演したこともあります。
**SIXTEENとTWICEでのデビュー**
2015年、JYPの新人ガールズグループTWICEのメンバーを選抜するサバイバル番組Mnet「SIXTEEN」に参加し、個性的なラップとステージマナーで注目を集めました。最終メンバーに抜擢され、2015年10月20日、TWICEの1stミニアルバム「THE STORY BEGINS」で正式にデビューしました。
**TWICEでの活動とソロ活動**
TWICEのメンバーとして、チェヨンは数多くのヒット曲に参加し、世界的な人気を得ることに貢献しました。グループ内では独特な歌声と安定したラップの実力で、楽曲の魅力を一層高めています。
また、多数のTWICEのアルバム収録曲の作詞に参加し、着実に音楽的な実力を伸ばしてきました。「PAGE TWO」収録の「Precious Love」のラップメイキングを皮切りに、「LIKEY」、「What is Love?」、「Feel Special」など、多数の楽曲制作に名を連ねています。
最近ではソロアーティストとしての活動も開始し、音楽の幅を広げています。');
        $jaImagePath = new ImagePath('/resources/public/images/after1.webp');
        $link4 = new ExternalContentLink('https://example4.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks2 = [$link4];
        $jaRelevantVideoLinks = new RelevantVideoLinks($externalContentLinks2);
        $jaMember = new DraftMember(
            $jaMemberIdentifier,
            $memberIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editorIdentifier,
            $japanese,
            $jaName,
            $jaRealName,
            $jaGroupIdentifiers,
            $jaBirthday,
            $jaCareer,
            $jaImagePath,
            $jaRelevantVideoLinks,
            ApprovalStatus::Pending,
        );

        $enMemberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $english = Translation::ENGLISH;
        $enName = new MemberName('Chae-young');
        $enRealName = new RealName('Son Chae-young');
        $enGroupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $enBirthday = new Birthday(new DateTimeImmutable('1999-04-23'));
        $enCareer = new Career('Son Chaeyoung is a member of the South Korean girl group TWICE. In the group, she serves as the main rapper and a sub-vocalist, and she has also shown her versatility as a multi-talented artist by participating in lyric writing and composition.
**Pre-Debut**
Showing a talent for dance from a young age, Chaeyoung passed the JYP Entertainment audition in 2012 and began her life as a trainee. Over a training period of about three years, she polished her rap and vocal skills. Before her debut, she also appeared in the music video for GOT7\'s "Stop stop it."
**SIXTEEN and Debut with TWICE**
In 2015, she participated in Mnet\'s survival show "SIXTEEN," a program designed to select the members for JYP\'s new girl group, TWICE. She gained attention for her unique rapping style and stage presence. She was selected as a final member and officially debuted on October 20, 2015, with TWICE\'s first mini-album, "THE STORY BEGINS."
**Activities with TWICE and Solo Career**
As a member of TWICE, Chaeyoung has contributed to the group\'s global popularity through numerous hit songs. Within the group, she enhances their music with her unique vocal tone and stable rapping skills.
Furthermore, she has consistently developed her musical abilities by writing lyrics for many of TWICE\'s album tracks. Starting with making the rap for "Precious Love" from the "PAGE TWO" album, she has been credited on numerous songs, including "LIKEY," "What is Love?," and "Feel Special."
Recently, she has also begun activities as a solo artist, further broadening her musical spectrum.');
        $enImagePath = new ImagePath('/resources/public/images/after2.webp');
        $link5 = new ExternalContentLink('https://example5.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks3 = [$link5];
        $enRelevantVideoLinks = new RelevantVideoLinks($externalContentLinks3);
        $enMember = new DraftMember(
            $enMemberIdentifier,
            $memberIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editorIdentifier,
            $english,
            $enName,
            $enRealName,
            $enGroupIdentifiers,
            $enBirthday,
            $enCareer,
            $enImagePath,
            $enRelevantVideoLinks,
            ApprovalStatus::Pending,
        );

        $memberRepository = Mockery::mock(MemberRepositoryInterface::class);
        $memberRepository->shouldReceive('findById')
            ->with($memberIdentifier)
            ->once()
            ->andReturn($member);
        $memberRepository->shouldReceive('saveDraft')
            ->with($enMember)
            ->once()
            ->andReturn(null);
        $memberRepository->shouldReceive('saveDraft')
            ->with($jaMember)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateMember')
            ->with($member, $english)
            ->once()
            ->andReturn($enMember);
        $translationService->shouldReceive('translateMember')
            ->with($member, $japanese)
            ->once()
            ->andReturn($jaMember);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(MemberRepositoryInterface::class, $memberRepository);
        $translateMember = $this->app->make(TranslateMemberInterface::class);
        $members = $translateMember->process($input);
        $this->assertCount(2, $members);
        $this->assertSame($jaMember, $members[0]);
        $this->assertSame($enMember, $members[1]);
    }

    /**
     * 異常系： 指定したIDのメンバー情報が見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testWhenMemberNotFound(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $input = new TranslateMemberInput(
            $memberIdentifier,
        );

        $memberRepository = Mockery::mock(MemberRepositoryInterface::class);
        $memberRepository->shouldReceive('findById')
            ->with($memberIdentifier)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(MemberRepositoryInterface::class, $memberRepository);

        $this->expectException(MemberNotFoundException::class);
        $translateMember = $this->app->make(TranslateMemberInterface::class);
        $translateMember->process($input);
    }
}
