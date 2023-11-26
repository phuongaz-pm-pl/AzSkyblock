<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\world\custom;

use czechpmdevs\multiworld\util\WorldUtils;
use phuongaz\azskyblock\AzSkyBlock;
use pocketmine\math\Vector3;
use pocketmine\Server;

class IslandPool {

    private static $pool = [];

    public static function add(string $name, CustomIsland $island) : void {
        self::$pool[$name] = $island;
    }

    public static function get(string $name) : ?CustomIsland {
        return self::$pool[$name] ?? null;
    }

    public static function remove(string $name) : void {
        unset(self::$pool[$name]);
    }

    public static function getAll() : array {
        return self::$pool;
    }

    public static function getRandom() : ?CustomIsland {
        $islands = self::getAll();
        if(empty($islands)) {
            return null;
        }
        $random = array_rand($islands);
        return $islands[$random];
    }

    public static function loads() : void {
        $path = AzSkyBlock::getInstance()->getDataFolder() . "islands/";
        $count = 0;
        foreach(scandir($path) as $file) {
            if($file === "." || $file === "..") {
                continue;
            }
            $data = json_decode(file_get_contents($path . $file), true);
            $name = $data["name"];
            $spawnPosition = new Vector3($data["spawn_position"]["x"], $data["spawn_position"]["y"], $data["spawn_position"]["z"]);
            $position1 = new Vector3($data["position_1"]["x"], $data["position_1"]["y"], $data["position_1"]["z"]);
            $position2 = new Vector3($data["position_2"]["x"], $data["position_2"]["y"], $data["position_2"]["z"]);
            if(($world = WorldUtils::getLoadedWorldByName($data["world"])) === null) {
                continue;
            }
            $island = new CustomIsland($name, $data["description"], $data["island_image"], $world, $spawnPosition, $position1, $position2, unserialize(base64_decode($data["blocks"])));
            self::add($name, $island);
            $count++;
        }
        AzSkyBlock::getInstance()->getLogger()->info("Loaded $count custom islands");
    }
}