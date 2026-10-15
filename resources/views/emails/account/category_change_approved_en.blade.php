@extends('emails.account.approval', [
    'locale' => 'en',
    'heading' => 'Your category change has been approved',
    'intro' => 'Your request to change your account category has been approved.',
    'detailLabel' => 'New account category',
    'detailValue' => $categoryLabel,
    'approvalUrl' => $categoryChangeUrl,
    'next' => 'The change is already in effect. You can review it in your account settings.',
    'button' => 'View account category',
    'fallback' => 'If the button does not open, copy the URL below into your browser.',
])
