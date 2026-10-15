<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Service;

use Application\Mail\AccountCategoryChangeApprovedMail;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class AccountCategoryChangeApprovedMailTest extends TestCase
{
    public function testRendersApprovalAndLocalizedCategoryInHtmlAndTextForEveryLanguage(): void
    {
        view()->addLocation(dirname(__DIR__, 5) . '/resources/views');
        $labels = [
            'ja' => ['agency' => '事務所', 'talent' => 'タレント', 'general' => '一般'],
            'en' => ['agency' => 'Agency', 'talent' => 'Talent', 'general' => 'General'],
            'ko' => ['agency' => '소속사', 'talent' => '탤런트', 'general' => '일반'],
        ];
        $subjects = [
            'ja' => 'アカウントのカテゴリ変更申請が承認されました',
            'en' => 'Your account category change has been approved',
            'ko' => '계정 카테고리 변경 신청이 승인되었습니다',
        ];
        $url = 'https://frontend.example.com/admin/account/category-change';
        foreach (Language::cases() as $language) {
            foreach (AccountCategory::cases() as $category) {
                $mail = new AccountCategoryChangeApprovedMail($url, $category, $language);
                $this->assertSame($subjects[$language->value], $mail->envelope()->subject);
                $mail->assertSeeInHtml($labels[$language->value][$category->value]);
                $mail->assertSeeInText($labels[$language->value][$category->value]);
                $mail->assertSeeInHtml($url);
                $mail->assertSeeInText($url);
                $mail->assertSeeInHtml('k-pool');
                $mail->assertSeeInText('k-pool');
            }
        }
    }
}
