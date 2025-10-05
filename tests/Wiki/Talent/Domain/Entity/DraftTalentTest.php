<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftTalentTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function test__construct(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $editIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $relevantVideoLinks = new RelevantVideoLinks([$link1, $link2, $link3]);
        $status = ApprovalStatus::Pending;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );
        $this->assertSame((string)$talentIdentifier, (string)$talent->talentIdentifier());
        $this->assertSame((string)$publishedTalentIdentifier, (string)$talent->publishedTalentIdentifier());
        $this->assertSame((string)$editIdentifier, (string)$talent->editorIdentifier());
        $this->assertSame($translation->value, $talent->translation()->value);
        $this->assertSame((string)$name, (string)$talent->name());
        $this->assertSame((string)$realName, (string)$talent->realName());
        $this->assertSame((string)$birthday, (string)$talent->birthday());
        $this->assertSame($groupIdentifiers, $talent->groupIdentifiers());
        $this->assertSame((string)$career, (string)$talent->career());
        $this->assertSame((string)$imagePath, (string)$talent->imageLink());
        $this->assertSame([(string)$link1, (string)$link2, (string)$link3], $talent->relevantVideoLinks()->toStringArray());
        $this->assertSame($status, $talent->status());
    }

    /**
     * 正常系：公開済みTalentIDのsetterが正しく動作すること.
     *
     * @throws ExceedMaxRelevantVideoLinksException
     * @return void
     */
    public function testSetPublishedTalentIdentifier(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $editIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $relevantVideoLinks = new RelevantVideoLinks([$link1, $link2, $link3]);
        $status = ApprovalStatus::Pending;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );
        $this->assertSame((string)$publishedTalentIdentifier, (string)$talent->publishedTalentIdentifier());

        $newPublishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $talent->setPublishedTalentIdentifier($newPublishedTalentIdentifier);
        $this->assertNotSame((string)$publishedTalentIdentifier, (string)$talent->publishedTalentIdentifier());
        $this->assertSame((string)$newPublishedTalentIdentifier, (string)$talent->publishedTalentIdentifier());
    }

    /**
     * 正常系：TalentNameのsetterが正しく動作すること.
     *
     * @throws ExceedMaxRelevantVideoLinksException
     * @return void
     */
    public function testSetName(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $editIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $relevantVideoLinks = new RelevantVideoLinks([$link1, $link2, $link3]);
        $status = ApprovalStatus::Pending;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );
        $this->assertSame((string)$name, (string)$talent->name());

        $newName = new TalentName('지효');
        $talent->setName($newName);
        $this->assertNotSame((string)$name, (string)$talent->name());
        $this->assertSame((string)$newName, (string)$talent->name());
    }

    /**
     * 正常系：RealNameのsetterが正しく動作すること.
     *
     * @throws ExceedMaxRelevantVideoLinksException
     * @return void
     */
    public function testSetRealName(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $editIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $relevantVideoLinks = new RelevantVideoLinks([$link1, $link2, $link3]);
        $status = ApprovalStatus::Pending;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );
        $this->assertSame((string)$realName, (string)$talent->realName());

        $newRealName = new RealName('박지수');
        $talent->setRealName($newRealName);
        $this->assertNotSame((string)$realName, (string)$talent->realName());
        $this->assertSame((string)$newRealName, (string)$talent->realName());
    }

    /**
     * 正常系：GroupIdentifierのsetterが正しく動作すること(null許容).
     *
     * @throws ExceedMaxRelevantVideoLinksException
     * @return void
     */
    public function testSetGroupIdentifier(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $editIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $relevantVideoLinks = new RelevantVideoLinks([$link1, $link2, $link3]);
        $status = ApprovalStatus::Pending;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );
        $this->assertSame($groupIdentifiers, $talent->groupIdentifiers());

        $newGroupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $talent->setGroupIdentifiers($newGroupIdentifiers);
        $this->assertNotSame($groupIdentifiers, $talent->groupIdentifiers());
        $this->assertSame($newGroupIdentifiers, $talent->groupIdentifiers());

        $talent->setGroupIdentifiers([]);
        $this->assertEmpty($talent->groupIdentifiers());
    }

    /**
     * 正常系：Birthdayのsetterが正しく動作すること.
     *
     * @throws ExceedMaxRelevantVideoLinksException
     * @return void
     */
    public function testSetBirthday(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $editIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $relevantVideoLinks = new RelevantVideoLinks([$link1, $link2, $link3]);
        $status = ApprovalStatus::Pending;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );
        $this->assertSame((string)$birthday, (string)$talent->birthday());

        $newBirthday = new Birthday(new DateTimeImmutable('1995-01-01'));
        $talent->setBirthday($newBirthday);
        $this->assertNotSame((string)$birthday, (string)$talent->birthday());
        $this->assertSame((string)$newBirthday, (string)$talent->birthday());
    }

    /**
     * 正常系：Careerのsetterが正しく動作すること.
     *
     * @throws ExceedMaxRelevantVideoLinksException
     * @return void
     */
    public function testSetCareer(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $editIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $relevantVideoLinks = new RelevantVideoLinks([$link1, $link2, $link3]);
        $status = ApprovalStatus::Pending;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );
        $this->assertSame((string)$career, (string)$talent->career());

        $newCareer = new Career('### **經歷介紹範例**
大學畢業後，我進入〇〇股份有限公司擔任業務，負責企業IT解決方案的新客戶開發及現有客戶的深度經營，至今已有四年經驗。我的強項是能深入傾聽客戶的潛在問題，並提出解決方案的「顧問式銷售」。憑藉此優勢，我於任職第三年達成了個人年度業績目標的120%，並榮獲公司內部的最佳業務MVP (最有價值員工)。
自2021年起，我轉職至事業公司的行銷部門，負責自家產品的行銷策略規劃與執行。我特別專注於數位行銷領域，透過網路廣告操作、SEO優化、社群內容企劃等方式，成功讓潛在客戶的獲取數量較前一年度提升了150%。此外，我擅長根據數據分析來改善策略，並使用Google Analytics等工具進行成效評估，以利制定下一步的策略。
我希望能活用至今在職涯中所累積的「精準掌握客戶問題的能力」以及「以數據為基礎制定並執行策略的能力」，為貴公司的業務成長做出貢獻。我相信未來能憑藉我同時具備業務與行銷的雙重觀點此一優勢，實現更有效的客戶開發與接觸。');

        $talent->setCareer($newCareer);
        $this->assertNotSame((string)$career, (string)$talent->career());
        $this->assertSame((string)$newCareer, (string)$talent->career());
    }

    /**
     * 正常系：ImageLinkのsetterが正しく動作すること.
     *
     * @return void
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testSetImageLink(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $editIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $relevantVideoLinks = new RelevantVideoLinks([$link1, $link2, $link3]);
        $status = ApprovalStatus::Pending;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );
        $this->assertSame((string)$imagePath, (string)$talent->imageLink());

        $newImagePath = new ImagePath('/resources/public/images/after.webp');

        $talent->setImageLink($newImagePath);
        $this->assertNotSame((string)$imagePath, (string)$talent->imageLink());
        $this->assertSame((string)$newImagePath, (string)$talent->imageLink());
    }

    /**
     * 正常系：RelevantVideoLinksのsetterが正しく動作すること.
     *
     * @return void
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testRelevantVideoLinks(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $editIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $relevantVideoLinks = new RelevantVideoLinks([$link1, $link2, $link3]);
        $status = ApprovalStatus::Pending;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );
        $this->assertSame($relevantVideoLinks->toStringArray(), $talent->relevantVideoLinks()->toStringArray());

        $link4 = new ExternalContentLink('https://example4.youtube.com/watch?v=dQw4w9WgXcQ');
        $link5 = new ExternalContentLink('https://example5.youtube.com/watch?v=dQw4w9WgXcQ');
        $newRelevantVideoLinks = new RelevantVideoLinks([$link4, $link5]);

        $talent->setRelevantVideoLinks($newRelevantVideoLinks);
        $this->assertNotSame($relevantVideoLinks->toStringArray(), $talent->relevantVideoLinks()->toStringArray());
        $this->assertSame($newRelevantVideoLinks->toStringArray(), $talent->relevantVideoLinks()->toStringArray());
    }

    /**
     * 正常系：Statusのsetterが正しく動作すること.
     *
     * @return void
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testSetStatus(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $editIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imagePath = new ImagePath('/resources/public/images/test.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $relevantVideoLinks = new RelevantVideoLinks([$link1, $link2, $link3]);
        $status = ApprovalStatus::Pending;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );
        $this->assertSame($status, $talent->status());

        $newStatus = ApprovalStatus::Approved;

        $talent->setStatus($newStatus);
        $this->assertNotSame($status, $talent->status());
        $this->assertSame($newStatus, $talent->status());
    }
}
