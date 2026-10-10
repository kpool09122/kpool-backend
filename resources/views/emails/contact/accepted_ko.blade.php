@extends('emails.contact.layout', ['locale' => 'ko', 'heading' => '문의가 접수되었습니다'])

@section('body')
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #555555;">문의해 주셔서 감사합니다.</p>
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #555555;">다음 내용으로 문의가 접수되었습니다.</p>
    <div style="background-color: #f8f9fa; border-radius: 8px; padding: 24px; font-size: 16px; line-height: 1.8; color: #333333; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;">{{ (string) $contact->content() }}</div>
@endsection
