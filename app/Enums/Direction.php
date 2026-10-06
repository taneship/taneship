<?php

declare(strict_types=1);

namespace App\Enums;

enum Direction: string
{
    case Ltr = 'ltr';
    case Rtl = 'rtl';

    private const array RIGHT_TO_LEFT_LANGUAGES = ['ar', 'ckb', 'dv', 'fa', 'he', 'ps', 'sd', 'ug', 'ur', 'yi'];

    public static function forLocale(string $locale): self
    {
        // The language alone decides: ar, ar_EG and ar-EG read the same way.
        $language = strtolower(substr($locale, 0, strcspn($locale, '_-')));

        return in_array($language, self::RIGHT_TO_LEFT_LANGUAGES, true) ? self::Rtl : self::Ltr;
    }
}
