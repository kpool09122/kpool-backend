@include('emails.passkey_recovery.layout', [
    'locale' => 'ja',
    'heading' => 'パスキーの復旧が完了しました',
    'message' => '新しいパスキーの登録が完了し、以前のパスキーはすべて削除されました。',
    'detail' => '既存のログインセッションはすべて無効になりました。',
    'notice' => 'この操作に心当たりがない場合は、すぐにアカウントを保護してください。',
])
