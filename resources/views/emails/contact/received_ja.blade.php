お問い合わせが届きました。

名前: {{ (string) $contact->name() }}
メールアドレス: {{ (string) $contact->email() }}
カテゴリ: {{ $contact->category()->value }}

{{ (string) $contact->content() }}
