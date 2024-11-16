<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\provider\cache;

use phuongaz\azskyblock\AzSkyBlock;
use phuongaz\azskyblock\island\Island;
use SOFe\AwaitGenerator\Await;

class Cache {

    /** @var $islandCache array<string, Island>*/
    private static array $islandCache;

    public static function getOrLoadIsland(string $player) : ?Island {
        if(isset(self::$islandCache[$player])) {
            return self::$islandCache[$player];
        }

        self::loadIsland($player);

        return self::$islandCache[$player] ?? null;
    }

    public static function getIsland(string $player) : ?Island {
        return self::$islandCache[$player] ?? null;
    }

    public static function loadIsland(string $player) : void {
        $provider = AzSkyBlock::getInstance()->getProvider();
        Await::g2c($provider->awaitGet($player, function(?Island $island) use ($player) {
            if(!isset(self::$islandCache[$player]) && $island !== null) {
                self::$islandCache[$player] = $island;
            }
        }));
    }

    public static function getIslandCache() : array {
        return self::$islandCache;
    }

    public static function unloadIsland(string $player) : void {
        unset(self::$islandCache[$player]);
    }

    public static function reloadIsland(string $player) : void {
        self::unloadIsland($player);
        self::loadIsland($player);
    }

    public static function unloadAll() : void {
        self::$islandCache = [];
    }
}