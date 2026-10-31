@extends('emails.layout', ['locale' => $contact->language()->value, 'heading' => $copy['submitterSubject']])

@section('body')
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.8; color: #555555;">{{ $copy['submitterIntro'] }}</p>
    @include('emails.contact.partials.details', ['showSender' => true])
    @include('emails.contact.partials.message', ['label' => $copy['replyLabel'], 'message' => (string) $replyContent])
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.8; color: #555555;">{{ $copy['managementNext'] }}</p>
    @include('emails.contact.partials.action', ['url' => $copy['siteUrl'], 'label' => $copy['openSite'], 'address' => $copy['siteUrl'], 'isSiteLink' => true])
@endsection
