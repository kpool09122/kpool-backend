@extends('emails.layout', ['locale' => $copy['locale'], 'heading' => $copy['receivedSubject']])

@section('body')
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.8; color: #555555;">{{ $copy['receivedIntro'] }}</p>
    @include('emails.contact.partials.details', ['showSender' => true])
    @include('emails.contact.partials.message', ['label' => $copy['inquiryLabel'], 'message' => (string) $contact->content()])
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.8; color: #555555;">{{ $copy['managementNext'] }}</p>
    @include('emails.contact.partials.action', ['url' => $copy['managementUrl'], 'label' => $copy['openManagement'], 'address' => $copy['managementUrl'], 'isSiteLink' => true])
@endsection
