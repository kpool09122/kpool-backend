<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent;

enum MBTI: string
{
    case INTJ = 'INTJ';
    case INTP = 'INTP';
    case ENTJ = 'ENTJ';
    case ENTP = 'ENTP';
    case INFJ = 'INFJ';
    case INFP = 'INFP';
    case ENFJ = 'ENFJ';
    case ENFP = 'ENFP';
    case ISTJ = 'ISTJ';
    case ISFJ = 'ISFJ';
    case ESTJ = 'ESTJ';
    case ESFJ = 'ESFJ';
    case ISTP = 'ISTP';
    case ISFP = 'ISFP';
    case ESTP = 'ESTP';
    case ESFP = 'ESFP';
}
