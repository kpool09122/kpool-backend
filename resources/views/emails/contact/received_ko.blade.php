새 문의가 도착했습니다.

이름: {{ (string) $contact->name() }}
이메일 주소: {{ (string) $contact->email() }}
카테고리: {{ $contact->category()->value }}

{{ (string) $contact->content() }}
