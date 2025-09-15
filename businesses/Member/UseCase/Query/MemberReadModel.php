<?php

namespace Businesses\Member\UseCase\Query;

use DateTimeImmutable;

readonly class MemberReadModel
{
    public function __construct(
        private string $memberId,
        private string $name,
        private string $groupName,
        private DateTimeImmutable $birthday,
        private string $career,
        private string $imageUrl,
    ) {
    }

    public function memberId(): string
    {
        return $this->memberId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function groupName(): string
    {
        return $this->groupName;
    }

    public function birthday(): DateTimeImmutable
    {
        return $this->birthday;
    }

    public function career(): string
    {
        return $this->career;
    }

    public function imageUrl(): string
    {
        return $this->imageUrl;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'member_id' => $this->memberId,
            'name' => $this->name,
            'group_name' => $this->groupName,
            'birthday' => $this->birthday,
            'career' => $this->career,
            'image_url' => $this->imageUrl,
        ];
    }
}
