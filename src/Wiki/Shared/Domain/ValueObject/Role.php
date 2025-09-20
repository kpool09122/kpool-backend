<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

enum Role: string
{
    case AGENCY_ACTOR = 'agency_actor';
    case GROUP_ACTOR = 'group_actor';
    case MEMBER_ACTOR = 'member_actor';
    case COLLABORATOR = 'collaborator';
    case ADMINISTRATOR = 'administrator';

    /**
     * リソースタイプごとの基本許可（スコープは別チェックで扱う）
     * @return Action[]
     */
    public function allowedActionsFor(ResourceType $resource): array
    {
        return match($this) {
            self::AGENCY_ACTOR, self::ADMINISTRATOR => [Action::CREATE, Action::EDIT, Action::SUBMIT, Action::APPROVE, Action::TRANSLATE],
            self::GROUP_ACTOR, self::MEMBER_ACTOR => match($resource) {
                ResourceType::AGENCY => [Action::CREATE, Action::EDIT, Action::SUBMIT],
                default => [Action::CREATE, Action::EDIT, Action::SUBMIT, Action::APPROVE, Action::TRANSLATE],
            },
            self::COLLABORATOR => [Action::CREATE, Action::EDIT, Action::SUBMIT],
        };
    }

    /**
     * 権限判定（スコープチェック含む）
     */
    public function can(Action $action, ResourceIdentifier $resource, Actor $actor): bool
    {
        // 管理者は全許可
        if ($this === self::ADMINISTRATOR) {
            return true;
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
        // 要件: Agency actor も「自分のところに所属するグループと、そのメンバー、歌」しか承認・翻訳できない
        if ($this === self::AGENCY_ACTOR && ($action === Action::APPROVE || $action === Action::TRANSLATE)) {
            $actorAgencyId = $actor->agencyId();
            if ($actorAgencyId === null) {
                return false;
            }

            if ($resource->type() === ResourceType::AGENCY) {
                // 自分の事務所（agency）のみ
                return $resource->id() === $actorAgencyId;
            }

            if ($resource->type() === ResourceType::GROUP) {
                // group の agencyId が一致している必要がある
                return $resource->agencyId() !== null && $resource->agencyId() === $actorAgencyId;
            }

            if (in_array($resource->type(), [ResourceType::MEMBER, ResourceType::SONG], true)) {
                // member/song は属する group の所属 agency（resource の agencyId）で判定
                return $resource->agencyId() !== null && $resource->agencyId() === $actorAgencyId;
            }
        }

        // 要件: Group actor は「自身に紐づく Group と、その Group に紐づく Member/Song のみ承認可能」
        if ($this === self::GROUP_ACTOR && ($action === Action::APPROVE || $action === Action::TRANSLATE)) {
            // Group リソースの承認 -> resource の id が actor の所属グループに含まれるか
            if ($resource->type() === ResourceType::GROUP) {
                return in_array($resource->id(), $actor->groupIds(), true);
            }

            // Member または Song の承認 -> resource の groupIds と actor の所属グループが交差するか
            if (in_array($resource->type(), [ResourceType::MEMBER, ResourceType::SONG], true)) {
                $resourceGroupIds = $resource->groupIds();
                if (empty($resourceGroupIds)) {
                    return false;
                }

                return count(array_intersect($resourceGroupIds, $actor->groupIds())) > 0;
            }

            // Group actor は Agency を承認できない
            return false;
        }

        // Member actor の場合、もし承認スコープを「自分の所属グループ内のみ」としたければ同様にチェック可能
        if ($this === self::MEMBER_ACTOR && ($action === Action::APPROVE || $action === Action::TRANSLATE)) {
            if (in_array($resource->type(), [ResourceType::GROUP, ResourceType::MEMBER, ResourceType::SONG], true)) {
                $resourceGroupIds = $resource->groupIds();
                if (empty($resourceGroupIds)) {
                    return false;
                }

                return count(array_intersect($resourceGroupIds, $actor->groupIds())) > 0;
            }
        }

        // Collaborator 等は追加のスコープ条件なし（承認権がないためここまで来ない）
        return true;
    }
}
