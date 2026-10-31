@extends('emails.contact.layout', ['locale' => 'en', 'heading' => 'Reply to Your Inquiry'])

@section('body')
    <div style="background-color: #f8f9fa; border-radius: 8px; padding: 24px; font-size: 16px; line-height: 1.8; color: #333333; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;">{{ (string) $content }}</div>
    @if ($contact->principalIdentifier() !== null)
        <p style="margin: 24px 0 0; font-size: 16px; line-height: 1.8; color: #555555;">To view your reply or ask a follow-up question, sign in and open your inquiry history in the dashboard.</p>
    @else
        <p style="margin: 24px 0 0; font-size: 16px; line-height: 1.8; color: #555555;">For follow-up questions, please reply to this email address: <a href="mailto:{{ (string) $replyToAddress }}">{{ (string) $replyToAddress }}</a></p>
    @endif
@endsection
