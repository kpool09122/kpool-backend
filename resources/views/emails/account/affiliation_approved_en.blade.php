@extends('emails.account.approval', [
    'locale' => 'en',
    'heading' => 'Your affiliation request has been approved',
    'intro' => 'Your affiliation request has been approved, and the affiliation is now active.',
    'detailLabel' => 'Approved by',
    'detailValue' => $approverAccountName,
    'approvalUrl' => $approvalUrl,
    'next' => 'You can review the affiliation in your account settings.',
    'button' => 'View details',
    'fallback' => 'If the button does not open, copy the URL below into your browser.',
])
