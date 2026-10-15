<?php

declare(strict_types=1);

namespace Tests\Account\Notification\Infrastructure\Service;

use Application\Mail\AffiliationApprovedMail;
use Application\Mail\DelegationApprovedMail;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class AccountApprovalMailTest extends TestCase
{
    public function testRendersLocalizedApprovalAndLinksInHtmlAndText(): void
    {
        view()->addLocation(dirname(__DIR__, 5) . '/resources/views');
        $labels = [
            'ja' => ['アフィリエーション申請が承認されました', 'デレゲーション申請が承認されました'],
            'en' => ['Your affiliation request has been approved', 'Your delegation request has been approved'],
            'ko' => ['소속 신청이 승인되었습니다', '권한 위임 신청이 승인되었습니다'],
        ];
        foreach (Language::cases() as $language) {
            $url = 'https://frontend.example.com/admin/account/affiliations';
            $delegationUrl = 'https://frontend.example.com/admin/account/delegations';
            foreach ([new AffiliationApprovedMail($url, 'Example Agency', $language), new DelegationApprovedMail($delegationUrl, 'Example Agency', $language)] as $index => $mail) {
                $this->assertSame($labels[$language->value][$index], $mail->envelope()->subject);
                $mail->assertSeeInHtml($labels[$language->value][$index]);
                $mail->assertSeeInText(match ($language) {
                    Language::JAPANESE => '承認され',
                    Language::ENGLISH => 'approved',
                    Language::KOREAN => '승인',
                });
                $mail->assertSeeInHtml('Example Agency');
                $mail->assertSeeInText('Example Agency');
                $mail->assertSeeInHtml($index === 0 ? $url : $delegationUrl);
                $mail->assertSeeInText($index === 0 ? $url : $delegationUrl);
                $mail->assertSeeInHtml('k-pool');
                $mail->assertSeeInText('k-pool');
            }
        }
    }

    public function testEscapesApproverNameInHtml(): void
    {
        view()->addLocation(dirname(__DIR__, 5) . '/resources/views');
        foreach ([new AffiliationApprovedMail('https://frontend.example.com', '<script>alert(1)</script>', Language::JAPANESE), new DelegationApprovedMail('https://frontend.example.com', '<script>alert(1)</script>', Language::JAPANESE)] as $mail) {
            $html = $mail->render();
            $this->assertStringContainsString('&lt;script&gt;', $html);
            $this->assertStringNotContainsString('<script>', $html);
            $mail->assertSeeInText('<script>alert(1)</script>');
        }
    }
}
