@extends('emails.contact.layout', ['locale' => 'ja', 'heading' => 'お問い合わせを受け付けました'])

@section('body')
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #555555;">お問い合わせありがとうございます。</p>
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #555555;">以下の内容でお問い合わせを受け付けました。</p>
    <div style="background-color: #f8f9fa; border-radius: 8px; padding: 24px; font-size: 16px; line-height: 1.8; color: #333333; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;">{{ (string) $contact->content() }}</div>
@endsection
