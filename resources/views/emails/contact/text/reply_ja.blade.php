{!! (string) $content !!}

@if ($contact->principalIdentifier() !== null)
返信の確認や追加のご質問は、ログイン後、管理画面のお問い合わせ履歴からお願いいたします。
@else
追加のご質問は、こちらのメールアドレスまでご返信ください： {!! (string) $replyToAddress !!}
@endif

k-pool
