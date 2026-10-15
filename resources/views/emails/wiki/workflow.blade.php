@extends('emails.layout', ['locale' => $language->value, 'heading' => $copy['subject']])

@section('body')
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.8; color: #555555;">{{ $copy['intro'] }}</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 24px; background-color: #eff6ff; border-radius: 8px;">
        <tr>
            <td style="padding: 20px 24px;">
                <p style="margin: 0 0 8px; font-size: 13px; color: #555555;">{{ $copy['wikiLabel'] }}</p>
                <p style="margin: 0; font-size: 22px; line-height: 1.5; font-weight: bold; color: #1e40af; overflow-wrap: anywhere; word-break: break-word;">{{ $wikiName }}</p>
            </td>
        </tr>
    </table>
    @if ($rejectionReason !== null)
        <div style="margin-bottom: 24px; padding: 20px 24px; background-color: #fff1f2; border-radius: 8px;">
            <p style="margin: 0 0 8px; font-size: 13px; color: #9f1239;">{{ $copy['reasonLabel'] }}</p>
            <p style="margin: 0; font-size: 16px; line-height: 1.8; color: #333333; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;">{{ $rejectionReason }}</p>
        </div>
    @endif
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.8; color: #555555;">{{ $copy['next'] }}</p>
    @if ($wikiUrl !== null)
        <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 0 auto 24px;">
            <tr>
                <td align="center" bgcolor="#2563eb" style="border-radius: 8px;">
                    <a href="{{ $wikiUrl }}" style="display: inline-block; padding: 16px 24px; font-size: 16px; line-height: 1.5; font-weight: bold; color: #ffffff; text-decoration: none;">{{ $copy['button'] }}</a>
                </td>
            </tr>
        </table>
        <p style="margin: 0 0 8px; font-size: 12px; line-height: 1.8; color: #777777;">{{ $copy['fallback'] }}</p>
        <p style="margin: 0; font-size: 12px; line-height: 1.8; overflow-wrap: anywhere; word-break: break-all;"><a href="{{ $wikiUrl }}" style="color: #2563eb;">{{ $wikiUrl }}</a></p>
    @endif
@endsection
