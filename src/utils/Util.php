<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\utils;

use pocketmine\world\Position;

class Util {

    public static function convertToSlug(string $string) : string {
        $string = strtolower($string);
        $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
        $string = preg_replace("/[\s-]+/", " ", $string);
        $string = preg_replace("/[\s_]/", "-", $string);
        return $string;
    }

}