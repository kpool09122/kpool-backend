@extends('emails.contact.layout', ['locale' => 'ja', 'heading' => 'お問い合わせへの返信'])

@section('body')
    <div style="background-color: #f8f9fa; border-radius: 8px; padding: 24px; font-size: 16px; line-height: 1.8; color: #333333; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;">{{ (string) $content }}</div>
    @if ($contact->principalIdentifier() !== null)
        <p style="margin: 24px 0 0; font-size: 16px; line-height: 1.8; color: #555555;">返信の確認や追加のご質問は、ログイン後、管理画面のお問い合わせ履歴からお願いいたします。</p>
    @else
        <p style="margin: 24px 0 0; font-size: 16px; line-height: 1.8; color: #555555;">追加のご質問は、こちらのメールアドレスまでご返信ください： <a href="mailto:{{ (string) $replyToAddress }}">{{ (string) $replyToAddress }}</a></p>
    @endif
@endsection
