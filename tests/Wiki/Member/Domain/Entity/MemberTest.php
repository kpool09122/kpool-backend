<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Domain\Entity;

use Businesses\Shared\ValueObject\ExternalContentLink;
use Businesses\Shared\ValueObject\ImagePath;
use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Member\Domain\Entity\Member;
use Businesses\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Businesses\Wiki\Member\Domain\ValueObject\Birthday;
use Businesses\Wiki\Member\Domain\ValueObject\Career;
use Businesses\Wiki\Member\Domain\ValueObject\GroupIdentifier;
use Businesses\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Businesses\Wiki\Member\Domain\ValueObject\MemberName;
use Businesses\Wiki\Member\Domain\ValueObject\RealName;
use Businesses\Wiki\Member\Domain\ValueObject\RelevantVideoLinks;
use DateTimeImmutable;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class MemberTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function test__construct(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new MemberName('채영');
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
        $member = new Member(
            $memberIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
        );
        $this->assertSame((string)$memberIdentifier, (string)$member->memberIdentifier());
        $this->assertSame($translation->value, $member->translation()->value);
        $this->assertSame((string)$name, (string)$member->name());
        $this->assertSame((string)$realName, (string)$member->realName());
        $this->assertSame((string)$birthday, (string)$member->birthday());
        $this->assertSame($groupIdentifiers, $member->groupIdentifiers());
        $this->assertSame((string)$career, (string)$member->career());
        $this->assertSame((string)$imagePath, (string)$member->imageLink());
        $this->assertSame([(string)$link1, (string)$link2, (string)$link3], $member->relevantVideoLinks()->toStringArray());
    }

    /**
     * 正常系：MemberNameのsetterが正しく動作すること.
     *
     * @throws ExceedMaxRelevantVideoLinksException
     * @return void
     */
    public function testSetName(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new MemberName('채영');
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
        $member = new Member(
            $memberIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
        );
        $this->assertSame((string)$name, (string)$member->name());

        $newName = new MemberName('지효');
        $member->setName($newName);
        $this->assertNotSame((string)$name, (string)$member->name());
        $this->assertSame((string)$newName, (string)$member->name());
    }

    /**
     * 正常系：RealNameのsetterが正しく動作すること.
     *
     * @throws ExceedMaxRelevantVideoLinksException
     * @return void
     */
    public function testSetRealName(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new MemberName('채영');
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
        $member = new Member(
            $memberIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
        );
        $this->assertSame((string)$realName, (string)$member->realName());

        $newRealName = new RealName('박지수');
        $member->setRealName($newRealName);
        $this->assertNotSame((string)$realName, (string)$member->realName());
        $this->assertSame((string)$newRealName, (string)$member->realName());
    }

    /**
     * 正常系：GroupIdentifierのsetterが正しく動作すること(null許容).
     *
     * @throws ExceedMaxRelevantVideoLinksException
     * @return void
     */
    public function testSetGroupIdentifier(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new MemberName('채영');
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
        $member = new Member(
            $memberIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
        );
        $this->assertSame($groupIdentifiers, $member->groupIdentifiers());

        $newGroupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $member->setGroupIdentifiers($newGroupIdentifiers);
        $this->assertNotSame($groupIdentifiers, $member->groupIdentifiers());
        $this->assertSame($newGroupIdentifiers, $member->groupIdentifiers());

        $member->setGroupIdentifiers([]);
        $this->assertEmpty($member->groupIdentifiers());
    }

    /**
     * 正常系：Birthdayのsetterが正しく動作すること.
     *
     * @throws ExceedMaxRelevantVideoLinksException
     * @return void
     */
    public function testSetBirthday(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new MemberName('채영');
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
        $member = new Member(
            $memberIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
        );
        $this->assertSame((string)$birthday, (string)$member->birthday());

        $newBirthday = new Birthday(new DateTimeImmutable('1995-01-01'));
        $member->setBirthday($newBirthday);
        $this->assertNotSame((string)$birthday, (string)$member->birthday());
        $this->assertSame((string)$newBirthday, (string)$member->birthday());
    }

    /**
     * 正常系：Careerのsetterが正しく動作すること.
     *
     * @throws ExceedMaxRelevantVideoLinksException
     * @return void
     */
    public function testSetCareer(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new MemberName('채영');
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
        $member = new Member(
            $memberIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
        );
        $this->assertSame((string)$career, (string)$member->career());

        $newCareer = new Career('### **經歷介紹範例**
大學畢業後，我進入〇〇股份有限公司擔任業務，負責企業IT解決方案的新客戶開發及現有客戶的深度經營，至今已有四年經驗。我的強項是能深入傾聽客戶的潛在問題，並提出解決方案的「顧問式銷售」。憑藉此優勢，我於任職第三年達成了個人年度業績目標的120%，並榮獲公司內部的最佳業務MVP (最有價值員工)。
自2021年起，我轉職至事業公司的行銷部門，負責自家產品的行銷策略規劃與執行。我特別專注於數位行銷領域，透過網路廣告操作、SEO優化、社群內容企劃等方式，成功讓潛在客戶的獲取數量較前一年度提升了150%。此外，我擅長根據數據分析來改善策略，並使用Google Analytics等工具進行成效評估，以利制定下一步的策略。
我希望能活用至今在職涯中所累積的「精準掌握客戶問題的能力」以及「以數據為基礎制定並執行策略的能力」，為貴公司的業務成長做出貢獻。我相信未來能憑藉我同時具備業務與行銷的雙重觀點此一優勢，實現更有效的客戶開發與接觸。');

        $member->setCareer($newCareer);
        $this->assertNotSame((string)$career, (string)$member->career());
        $this->assertSame((string)$newCareer, (string)$member->career());
    }

    /**
     * 正常系：ImageLinkのsetterが正しく動作すること.
     *
     * @return void
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testSetImageLink(): void
    {
        $memberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new MemberName('채영');
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
        $imagePath = new ImagePath('/resources/public/images/before.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $relevantVideoLinks = new RelevantVideoLinks([$link1, $link2, $link3]);
        $member = new Member(
            $memberIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
        );
        $this->assertSame((string)$imagePath, (string)$member->imageLink());

        $newImagePath = new ImagePath('/resources/public/images/after.webp');

        $member->setImageLink($newImagePath);
        $this->assertNotSame((string)$imagePath, (string)$member->imageLink());
        $this->assertSame((string)$newImagePath, (string)$member->imageLink());
    }
}
