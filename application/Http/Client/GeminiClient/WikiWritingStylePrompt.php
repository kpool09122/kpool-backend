<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient;

final class WikiWritingStylePrompt
{
    public static function instructions(string $language): string
    {
        if ($language !== 'ja') {
            return '';
        }

        return <<<PROMPT
## Writing Style
日本語の説明文は、百科事典に適した客観的で自然な「である調」で記述する。文の意味に応じて「メンバーである」「担当している」「デビューを果たした」「親しまれている」のような語尾を使い分ける。各文の末尾に機械的に「だ」や「である」を付けない。概要、沿革、各節の本文、SEOの説明文にも適用し、敬体（です・ます調）は使わない。
PROMPT;
    }
}
