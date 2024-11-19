<?php

declare(strict_types=1);

namespace phuongaz\azskyblock;

use Closure;
use Generator;
use phuongaz\azskyblock\island\Island;
use phuongaz\azskyblock\provider\cache\Cache;
use phuongaz\azskyblock\utils\IslandSettings;
use phuongaz\azskyblock\world\WorldUtils;
use pocketmine\world\Position;
use SOFe\AwaitGenerator\Await;

class AZSkyblockAPI {

    public static function getIslandFromPos(Position $position) : ?Island {
        $world = IslandSettings::getConfig()->get("world");

        if(!$position->getWorld()->getFolderName() === $world) {
            return null;
        }

        $islands = Cache::getIslandCache();
        foreach($islands as $owner => $island) {
            if($island->getArea()->isInArea($position)) {
                return $island;
            }
        }

        return null;
    }
}