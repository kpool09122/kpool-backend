<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Source\Shared\Domain\ValueObject\Language;

class VideoWatchRequestDigestMail extends Mailable
{
    private const array COPY = [
        'ja' => [
            'subject' => 'これみてリクエストが届いています',
            'intro' => 'あなたの公式Wikiに届いた新着のこれみてリクエストをまとめました。',
            'deleted' => '削除済みの動画',
            'recentCount' => 'リクエスト件数（直近7日間）',
            'newCount' => '新着件数',
            'featured' => '新着の動画',
            'count' => '新着リクエスト',
            'likes' => 'いいね',
            'thumbnailUnavailable' => 'サムネイルを表示できません',
            'button' => '動画を見る',
        ],
        'en' => [
            'subject' => 'Your video watch request digest',
            'intro' => 'Here are the new video watch requests for your official Wikis.',
            'deleted' => 'Deleted video',
            'recentCount' => 'Requests in the last 7 days',
            'newCount' => 'New requests',
            'featured' => 'New videos',
            'count' => 'New requests',
            'likes' => 'Likes',
            'thumbnailUnavailable' => 'Thumbnail unavailable',
            'button' => 'Watch videos',
        ],
        'ko' => [
            'subject' => '시청 요청이 도착했습니다',
            'intro' => '공식 Wiki에 도착한 새로운 시청 요청을 모았습니다.',
            'deleted' => '삭제된 동영상',
            'recentCount' => '최근 7일간 요청 수',
            'newCount' => '새 요청 수',
            'featured' => '새 동영상',
            'count' => '새 요청',
            'likes' => '좋아요',
            'thumbnailUnavailable' => '썸네일을 표시할 수 없습니다',
            'button' => '동영상 보기',
        ],
    ];

    /**
     * @param list<array{wikiTitle: string, videoTitle: ?string, thumbnailUrl: ?string, likeCount: int, requestCount: int}> $videos
     */
    public function __construct(
        public readonly array $videos,
        public readonly int $recentRequestCount,
        public readonly int $newRequestCount,
        public readonly string $videosUrl,
        public readonly Language $language,
    ) {
        $this->locale($language->value);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::COPY[$this->language->value]['subject']);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.wiki.video-watch-digest', text: 'emails.wiki.text.video-watch-digest', with: ['copy' => self::COPY[$this->language->value]]);
    }
}
