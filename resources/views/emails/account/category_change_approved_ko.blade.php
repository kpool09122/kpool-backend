@extends('emails.account.approval', [
    'locale' => 'ko',
    'heading' => '카테고리 변경 신청이 승인되었습니다',
    'intro' => '계정 카테고리 변경 신청이 승인되었습니다.',
    'detailLabel' => '변경된 카테고리',
    'detailValue' => $categoryLabel,
    'approvalUrl' => $categoryChangeUrl,
    'next' => '변경 사항은 이미 반영되었습니다. 관리 화면에서 확인하실 수 있습니다.',
    'button' => '계정 카테고리 확인하기',
    'fallback' => '버튼이 열리지 않으면 아래 URL을 복사하여 브라우저에 붙여 넣어 주세요.',
])
