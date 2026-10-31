{!! (string) $content !!}

@if ($contact->principalIdentifier() !== null)
답변 확인이나 추가 문의는 로그인 후 관리 화면의 문의 내역에서 해 주세요.
@else
추가 문의는 다음 이메일 주소로 회신해 주세요: {!! (string) $replyToAddress !!}
@endif

k-pool
