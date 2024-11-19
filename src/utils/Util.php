<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\utils;

use phuongaz\azskyblock\AzSkyBlock;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class Util {

    public static function convertToSlug(string $string) : string {
        $string = strtolower($string);
        $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
        $string = preg_replace("/[\s-]+/", " ", $string);
        return preg_replace("/[\s_]/", "-", $string);
    }

    public static function praseFormat(string $content) {
        $config = AzSkyBlock::getInstance()->getConfig()->get("button-format");

        if($config === null) {
            return $content;
        }

        $content = str_replace("%content%", $content, $config);
        return TextFormat::colorize($content);
    }

}