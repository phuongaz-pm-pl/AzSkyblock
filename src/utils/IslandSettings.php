<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\utils;

use phuongaz\azskyblock\AzSkyBlock;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\Config;

class IslandSettings {

    public static function getConfig() : Config {
        return AzSkyBlock::getInstance()->getConfig();
    }

    public static function getMaxSize() : int {
        return self::getConfig()->get("island-size");
    }

    /**
     * @return Item[]
     */
    public static function getStartItems() : array {
        $items = self::getConfig()->get("start-items");
        $result = [];
        foreach($items as $itemData) {
            $itemObj = StringToItemParser::getInstance()->parse($itemData["name"]);
            if($itemData !== null) {
                $itemObj->setCount($itemData["amount"]);
                $result[] = $itemObj;
            }
        }
        return $result;
    }
}