<?php

namespace App\Event;

enum GroupMembershipStatus: string
{
    case GRANTED = 'granted';
    case REVOKED = 'revoked';
}