<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Service;

use Application\Mail\WikiWorkflowMail;
use Application\Mail\WikiWorkflowMailType;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class WikiWorkflowMailTest extends TestCase
{
    public function testAllOutcomesRenderInAllLanguagesWithTitleAndCorrectLink(): void
    {
        view()->addLocation(dirname(__DIR__, 5) . '/resources/views');
        $subjects = [
            'ja' => ['Wikiが承認されました', 'Wikiの申請が拒否されました', 'Wikiが公開されました'],
            'en' => ['Your Wiki has been approved', 'Your Wiki submission has been rejected', 'Your Wiki has been published'],
            'ko' => ['Wiki가 승인되었습니다', 'Wiki 신청이 거절되었습니다', 'Wiki가 공개되었습니다'],
        ];
        foreach (Language::cases() as $language) {
            foreach (WikiWorkflowMailType::cases() as $index => $type) {
                $reason = $type === WikiWorkflowMailType::REJECTED ? "Please add sources.\nCheck the date." : null;
                $url = 'https://frontend.example.com/ko/wiki/gr-example';
                $mail = new WikiWorkflowMail($type, 'Example & Friends', $type === WikiWorkflowMailType::APPROVED ? null : $url, $language, $reason);
                $this->assertSame($subjects[$language->value][$index], $mail->envelope()->subject);
                $mail->assertSeeInHtml($subjects[$language->value][$index]);
                $mail->assertSeeInHtml('Example & Friends');
                $mail->assertSeeInText('Example & Friends');
                if ($type === WikiWorkflowMailType::APPROVED) {
                    $mail->assertDontSeeInHtml($url);
                    $mail->assertDontSeeInText($url);
                    $this->assertStringNotContainsString('<a ', $mail->render());
                } else {
                    $mail->assertSeeInHtml($url);
                    $mail->assertSeeInText($url);
                }
                $mail->assertSeeInHtml('k-pool');
                if ($reason !== null) {
                    $mail->assertSeeInHtml($reason);
                    $mail->assertSeeInText($reason);
                } else {
                    $mail->assertDontSeeInHtml('Please add sources.');
                }
            }
        }
    }

    public function testWikiNameAndRejectionReasonAreEscapedInHtmlAndPreservedInText(): void
    {
        view()->addLocation(dirname(__DIR__, 5) . '/resources/views');
        $mail = new WikiWorkflowMail(WikiWorkflowMailType::REJECTED, '<script>name</script>', 'https://frontend.example.com', Language::JAPANESE, '<script>reason</script>');
        $html = $mail->render();
        $this->assertStringNotContainsString('<script>', $html);
        $mail->assertSeeInHtml('<script>name</script>');
        $mail->assertSeeInHtml('<script>reason</script>');
        $mail->assertSeeInText('<script>reason</script>');
    }
}
