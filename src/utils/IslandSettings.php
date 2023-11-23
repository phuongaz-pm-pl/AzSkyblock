<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\utils;

use phuongaz\azskyblock\AzSkyBlock;
use pocketmine\utils\Config;

class IslandSettings {

    public static function getConfig() : Config {
        return AzSkyBlock::getInstance()->getConfig();
    }

    public static function getMaxSize() : int {
        return self::getConfig()->get("island-size");
    }
}