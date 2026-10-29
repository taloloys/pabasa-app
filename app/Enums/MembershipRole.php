<?php

namespace App\Enums;

enum MembershipRole: string
{
    case ChurchAdmin = 'church_admin';
    case Lector = 'lector';
}
