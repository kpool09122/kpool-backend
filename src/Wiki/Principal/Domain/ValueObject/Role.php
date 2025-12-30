<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\ValueObject;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

enum Role: string
{
    case AGENCY_ACTOR = 'agency_actor';
    case GROUP_ACTOR = 'group_actor';
    case TALENT_ACTOR = 'talent_actor';
    case SENIOR_COLLABORATOR = 'senior_collaborator';
    case COLLABORATOR = 'collaborator';
    case ADMINISTRATOR = 'administrator';
    case NONE = 'none';

    /**
     * リソースタイプごとの基本許可（スコープは別チェックで扱う）
     * @return Action[]
     */
    public function allowedActionsFor(ResourceType $resource): array
    {
        return match($this) {
            self::AGENCY_ACTOR, self::ADMINISTRATOR, self::SENIOR_COLLABORATOR => [Action::CREATE, Action::EDIT, Action::SUBMIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH],
            self::GROUP_ACTOR, self::TALENT_ACTOR => match($resource) {
                ResourceType::AGENCY => [Action::CREATE, Action::EDIT, Action::SUBMIT],
                default => [Action::CREATE, Action::EDIT, Action::SUBMIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH],
            },
            self::COLLABORATOR => [Action::CREATE, Action::EDIT, Action::SUBMIT],
            self::NONE => [],
        };
    }

    /**
     * 権限判定（スコープチェック含む）
     */
    public function can(Action $action, ResourceIdentifier $resource, Principal $principal): bool
    {
        // 管理者とシニアコラボレーターは全許可
        if ($this === self::ADMINISTRATOR ||
            $this === self::SENIOR_COLLABORATOR) {
            return true;
        }

        //　ロールなしはすべて否認
        if ($this === self::NONE) {
            return false;
        }

        // 基本的なアクション許可を確認
        $allowed = false;
        $allowedActions = $this->allowedActionsFor($resource->type());
        foreach ($allowedActions as $allowedAction) {
            if ($allowedAction === $action) {
                $allowed = true;

                break;
            }
        }
        if (! $allowed) {
            return false;
        }

        // Agency actor のスコープチェック
        // 要件: Agency actor は「自分のところに所属するGroupと、Talent、Song」しか承認・却下・翻訳・公開できない
        if ($this === self::AGENCY_ACTOR && in_array($action, [Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH], true)) {
            $principalAgencyId = $principal->agencyId();
            if ($principalAgencyId === null) {
                return false;
            }

            if ($resource->type() === ResourceType::AGENCY) {
                // 自分の事務所（agency）のみ
                return $resource->agencyId() === $principalAgencyId;
            }

            if ($resource->type() === ResourceType::GROUP) {
                // group の agencyId が一致している必要がある
                return $resource->agencyId() !== null && $resource->agencyId() === $principalAgencyId;
            }

            if (in_array($resource->type(), [ResourceType::TALENT, ResourceType::SONG], true)) {
                // talent/song は属する group の所属 agency（resource の agencyId）で判定
                return $resource->agencyId() !== null && $resource->agencyId() === $principalAgencyId;
            }
        }

        // 要件: Group actor は「自身に紐づく Group と、その Group に紐づく Talent/Song のみ承認・却下・翻訳・公開可能」
        if ($this === self::GROUP_ACTOR && in_array($action, [Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH], true)) {
            // Group, Talent, Song の承認・却下・翻訳・公開 -> resource の groupIds と actor の所属グループが交差するか
            if (in_array($resource->type(), [ResourceType::GROUP, ResourceType::TALENT, ResourceType::SONG], true)) {
                $resourceGroupIds = $resource->groupIds();
                if (empty($resourceGroupIds)) {
                    return false;
                }

                return count(array_intersect($resourceGroupIds, $principal->groupIds())) > 0;
            }

            // Group actor は Agency を承認・却下・翻訳・公開できない
            return false;
        }

        // Talent actor のスコープチェック
        // - Group: 所属グループの修正・承認・却下・翻訳・公開のみ
        // - Talent: 自分自身かつ所属グループに紐づくもののみ
        // - Song: 所属グループまたは自分自身に紐づくもののみ
        // - Agency: 編集・承認・却下・翻訳・公開は不可
        if ($this === self::TALENT_ACTOR && in_array($action, [Action::EDIT, Action::APPROVE, Action::REJECT, Action::TRANSLATE, Action::PUBLISH], true)) {
            if ($resource->type() === ResourceType::GROUP) {
                $resourceGroupIds = $resource->groupIds();
                if (empty($resourceGroupIds)) {
                    return false;
                }

                return count(array_intersect($resourceGroupIds, $principal->groupIds())) > 0;
            }

            if ($resource->type() === ResourceType::TALENT) {
                $principalTalentIds = $principal->talentIds();
                $resourceTalentIds = $resource->talentIds();
                $principalGroupIds = $principal->groupIds();
                $resourceGroupIds = $resource->groupIds();
                if (empty($principalTalentIds) || empty($resourceTalentIds) || empty($principalGroupIds) || empty($resourceGroupIds)) {
                    return false;
                }

                return count(array_intersect($resourceTalentIds, $principalTalentIds)) > 0
                    && count(array_intersect($resourceGroupIds, $principalGroupIds)) > 0;
            }

            if ($resource->type() === ResourceType::SONG) {
                $principalTalentIds = $principal->talentIds();
                $resourceTalentIds = $resource->talentIds();
                $byOwnTalent = ! empty($principalTalentIds) && ! empty($resourceTalentIds)
                    && count(array_intersect($resourceTalentIds, $principalTalentIds)) > 0;

                $resourceGroupIds = $resource->groupIds();
                $byOwnGroup = ! empty($resourceGroupIds) && count(array_intersect($resourceGroupIds, $principal->groupIds())) > 0;

                return $byOwnTalent || $byOwnGroup;
            }

            // Talent actor は Agency を編集・承認・却下・翻訳・公開できない
            return false;
        }

        // Collaborator 等は追加のスコープ条件なし（承認・却下・翻訳・公開権がないためここまで来ない）
        return true;
    }
}
