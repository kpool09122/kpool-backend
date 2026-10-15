@extends('emails.account.approval', [
    'locale' => 'en',
    'heading' => 'Your delegation request has been approved',
    'intro' => 'Your delegation request has been approved.',
    'detailLabel' => 'Approved by',
    'detailValue' => $approverAccountName,
    'approvalUrl' => $approvalUrl,
    'next' => 'You can review the delegation in your account settings.',
    'button' => 'View details',
    'fallback' => 'If the button does not open, copy the URL below into your browser.',
])
