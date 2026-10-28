@extends('emails.layout', ['locale' => $language->value, 'heading' => $copy['subject']])

@section('body')
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.8; color: #555555;">{{ $copy['intro'] }}</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 24px; background-color: #eff6ff; border-radius: 8px;">
        <tr>
            <td width="50%" style="padding: 20px 16px; vertical-align: top;">
                <p style="margin: 0 0 8px; font-size: 13px; color: #555555;">{{ $copy['recentCount'] }}</p>
                <p style="margin: 0; font-size: 28px; font-weight: bold; color: #1e40af;">{{ $recentRequestCount }}</p>
            </td>
            <td width="50%" style="padding: 20px 16px; vertical-align: top;">
                <p style="margin: 0 0 8px; font-size: 13px; color: #555555;">{{ $copy['newCount'] }}</p>
                <p style="margin: 0; font-size: 28px; font-weight: bold; color: #1e40af;">{{ $newRequestCount }}</p>
            </td>
        </tr>
    </table>
    <h2 style="margin: 0 0 16px; font-size: 18px; line-height: 1.5; color: #222222;">{{ $copy['featured'] }}</h2>
    @foreach ($videos as $video)
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 20px; border: 1px solid #eeeeee; border-radius: 12px;">
            <tr>
                <td style="padding: 16px;">
                    <p style="margin: 0 0 8px; font-size: 13px; color: #777777;">{{ $video['wikiTitle'] }}</p>
                    <h3 style="margin: 0 0 12px; font-size: 18px; line-height: 1.5; color: #222222; overflow-wrap: anywhere;">{{ $video['videoTitle'] ?? $copy['deleted'] }}</h3>
                    @if ($video['thumbnailUrl'] !== null)
                        <img src="{{ $video['thumbnailUrl'] }}" alt="{{ $video['videoTitle'] ?? $copy['deleted'] }}" width="504" style="display: block; width: 100%; max-width: 504px; height: auto; border: 0; border-radius: 8px;" referrerpolicy="no-referrer">
                    @else
                        <p style="margin: 0; padding: 32px 16px; background-color: #f4f4f4; border-radius: 8px; text-align: center; font-size: 13px; color: #777777;">{{ $copy['thumbnailUnavailable'] }}</p>
                    @endif
                    <p style="margin: 12px 0; font-size: 13px; color: #555555;">{{ $copy['likes'] }}: {{ $video['likeCount'] }} / {{ $copy['count'] }}: {{ $video['requestCount'] }}</p>
                </td>
            </tr>
        </table>
    @endforeach
    <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 24px auto 0;">
        <tr>
            <td align="center" bgcolor="#2563eb" style="border-radius: 8px;">
                <a href="{{ $videosUrl }}" style="display: inline-block; padding: 16px 32px; font-size: 16px; line-height: 1.5; font-weight: bold; color: #ffffff; text-decoration: none;">{{ $copy['button'] }}</a>
            </td>
        </tr>
    </table>
@endsection
