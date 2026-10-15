@extends('emails.account.approval', [
    'locale' => 'ja',
    'heading' => 'アフィリエーション申請が承認されました',
    'intro' => 'アフィリエーション申請が承認されました。',
    'detailLabel' => '承認したアカウント',
    'detailValue' => $approverAccountName,
    'approvalUrl' => $approvalUrl,
    'next' => '管理画面から内容をご確認いただけます。',
    'button' => '内容を確認する',
    'fallback' => 'ボタンが開けない場合は、以下のURLをブラウザにコピーしてください。',
])
