<?php

namespace App\Enum;

enum RestrictionType: string
{
    case NONE = 'NONE';
    case AGE = 'AGE';
    case GEOGRAPHIC = 'GEOGRAPHIC';
    case TOTAL_PARTICIPANTS = 'TOTAL_PARTICIPANTS';
}
