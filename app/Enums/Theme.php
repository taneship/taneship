<?php

declare(strict_types=1);

namespace App\Enums;

enum Theme: string
{
    case Light = 'light';
    case Dark = 'dark';
    case System = 'system';
}
