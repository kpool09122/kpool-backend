@extends('emails.contact.layout', ['locale' => 'ko', 'heading' => '문의에 대한 답변'])

@section('body')
    <div style="background-color: #f8f9fa; border-radius: 8px; padding: 24px; font-size: 16px; line-height: 1.8; color: #333333; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;">{{ (string) $content }}</div>
    @if ($contact->principalIdentifier() !== null)
        <p style="margin: 24px 0 0; font-size: 16px; line-height: 1.8; color: #555555;">답변 확인이나 추가 문의는 로그인 후 관리 화면의 문의 내역에서 해 주세요.</p>
    @else
        <p style="margin: 24px 0 0; font-size: 16px; line-height: 1.8; color: #555555;">추가 문의는 다음 이메일 주소로 회신해 주세요: <a href="mailto:{{ (string) $replyToAddress }}">{{ (string) $replyToAddress }}</a></p>
    @endif
@endsection
