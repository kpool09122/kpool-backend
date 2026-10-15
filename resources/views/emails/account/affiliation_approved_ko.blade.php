@extends('emails.account.approval', [
    'locale' => 'ko',
    'heading' => '소속 신청이 승인되었습니다',
    'intro' => '소속 신청이 승인되어 소속 관계가 활성화되었습니다.',
    'detailLabel' => '승인한 계정',
    'detailValue' => $approverAccountName,
    'approvalUrl' => $approvalUrl,
    'next' => '관리 화면에서 소속 내용을 확인하실 수 있습니다.',
    'button' => '내용 확인하기',
    'fallback' => '버튼이 열리지 않으면 아래 URL을 복사하여 브라우저에 붙여 넣어 주세요.',
])
