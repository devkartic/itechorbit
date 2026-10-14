<?php

namespace App\CustomHelpers;

class Helper
{
    public static function date_format($date): ?string
    {
        if (empty($date)) {
            return $date;
        }

        return date('Y-m-d', strtotime($date));
    }
}
