@extends('emails.layout', ['locale' => $copy['locale'], 'heading' => $copy['submitterSubject']])

@section('body')
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.8; color: #555555;">{{ $copy['submitterIntro'] }}</p>
    @include('emails.contact.partials.details', ['showSender' => true, 'showCategory' => false])
    @include('emails.contact.partials.message', ['label' => $copy['previousMessageLabel'], 'message' => $previousMessage])
    @include('emails.contact.partials.message', ['label' => $copy['replyLabel'], 'message' => (string) $replyContent])
    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.8; color: #555555;">{{ $copy['managementNext'] }}</p>
    @include('emails.contact.partials.action', ['url' => $copy['managementUrl'], 'label' => $copy['openManagement'], 'address' => $copy['managementUrl'], 'isSiteLink' => true])
@endsection
