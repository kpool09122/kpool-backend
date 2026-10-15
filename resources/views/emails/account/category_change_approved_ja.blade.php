@extends('emails.account.approval', [
    'locale' => 'ja',
    'heading' => 'カテゴリ変更申請が承認されました',
    'intro' => 'アカウントのカテゴリ変更申請が承認されました。',
    'detailLabel' => '変更後のカテゴリ',
    'detailValue' => $categoryLabel,
    'approvalUrl' => $categoryChangeUrl,
    'next' => '変更内容はすでに反映されています。管理画面からご確認いただけます。',
    'button' => 'アカウントのカテゴリを確認する',
    'fallback' => 'ボタンが開けない場合は、以下のURLをブラウザにコピーしてください。',
])
