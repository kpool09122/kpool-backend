@extends('emails.layout', ['locale' => $contact->language()->value, 'heading' => $copy['replySubject']])

@section('body')
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.8; color: #555555;">{{ $copy['replyIntro'] }}</p>
    @include('emails.contact.partials.details', ['showSender' => false])
    @include('emails.contact.partials.message', ['label' => $copy['replyLabel'], 'message' => (string) $content])
    @if ($contact->principalIdentifier() === null)
        <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.8; color: #555555;">{{ $copy['anonymousNext'] }}</p>
        @include('emails.contact.partials.action', ['url' => 'mailto:' . $replyToAddress, 'label' => $copy['emailButton'], 'address' => (string) $replyToAddress, 'isSiteLink' => false])
    @else
        <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.8; color: #555555;">{{ $copy['replyNext'] }}</p>
        @include('emails.contact.partials.action', ['url' => $copy['siteUrl'], 'label' => $copy['openSite'], 'address' => $copy['siteUrl'], 'isSiteLink' => true])
    @endif
@endsection
