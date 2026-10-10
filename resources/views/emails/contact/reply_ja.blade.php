@extends('emails.contact.layout', ['locale' => 'ja', 'heading' => 'お問い合わせへの返信'])

@section('body')
    <div style="background-color: #f8f9fa; border-radius: 8px; padding: 24px; font-size: 16px; line-height: 1.8; color: #333333; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;">{{ (string) $content }}</div>
@endsection
