<?php
namespace App\Util;

final class CacheKey
{
    /**
     * Build a PSR-6 safe cache key from parts.
     * Replaces all reserved characters with '-'.
     * (Alternatively, return 'k_'.md5($joined) if you prefer opaque keys.)
     */
    public static function make(string ...$parts): string
    {
        $joined = mb_strtolower(implode('|', $parts));
        return preg_replace('/[{}()\/\\\\@:]/', '-', $joined); // -> PSR-6 safe
    }
}
