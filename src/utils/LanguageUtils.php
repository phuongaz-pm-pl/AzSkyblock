<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\utils;

use phuongaz\azskyblock\AzSkyBlock;
use pocketmine\utils\TextFormat;

class LanguageUtils {

    public static function translate(string $key, array $params = []) : string {
        $language = AzSkyBlock::getInstance()->getLanguage();
        $message = $language->translateString($key, $params);
        return TextFormat::colorize($message);
    }

}