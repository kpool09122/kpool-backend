@extends('emails.contact.layout', ['locale' => 'en', 'heading' => 'We Have Received Your Inquiry'])

@section('body')
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #555555;">Thank you for contacting us.</p>
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #555555;">We have received your inquiry with the following details.</p>
    <div style="background-color: #f8f9fa; border-radius: 8px; padding: 24px; font-size: 16px; line-height: 1.8; color: #333333; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;">{{ (string) $contact->content() }}</div>
@endsection
