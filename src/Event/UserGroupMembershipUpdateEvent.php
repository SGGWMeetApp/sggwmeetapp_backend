<?php

namespace App\Event;

use App\Model\UserGroup;
use App\Security\User;
use Symfony\Contracts\EventDispatcher\Event;

class UserGroupMembershipUpdateEvent extends Event
{
    public const NAME = 'user_group_user.membership_change';

    private UserGroup $userGroup;

    private User $user;

    private GroupMembershipStatus $membershipStatus;

    /**
     * @param UserGroup $userGroup
     * @param User $user
     * @param GroupMembershipStatus $membershipStatus
     */
    public function __construct(UserGroup $userGroup, User $user, GroupMembershipStatus $membershipStatus)
    {
        $this->userGroup = $userGroup;
        $this->user = $user;
        $this->membershipStatus = $membershipStatus;
    }

    /**
     * @return UserGroup
     */
    public function getUserGroup(): UserGroup
    {
        return $this->userGroup;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return GroupMembershipStatus
     */
    public function getMembershipStatus(): GroupMembershipStatus
    {
        return $this->membershipStatus;
    }

}