<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\world\custom;

use phuongaz\azskyblock\AzSkyBlock;
use phuongaz\azskyblock\world\CustomIsland;
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
        if(!is_dir($path)) {
            mkdir($path);
        }

        $count = 0;
        foreach(scandir($path) as $file) {
            if($file === "." || $file === "..") {
                continue;
            }
            $name = str_replace(".json", "", $file);
            $data = json_decode(file_get_contents($path . $file), true);
            $spawnPosition = new Vector3($data["spawnPosition"]["x"], $data["spawnPosition"]["y"], $data["spawnPosition"]["z"]);
            $position1 = new Vector3($data["position1"]["x"], $data["position1"]["y"], $data["position1"]["z"]);
            $position2 = new Vector3($data["position2"]["x"], $data["position2"]["y"], $data["position2"]["z"]);
            $world = Server::getInstance()->getWorldManager()->getWorldByName($data["world"]);
            if($world === null) {
                continue;
            }
            $island = new CustomIsland($name, $spawnPosition, $position1, $position2, $world, $data["blocks"], $data["description"], $data["islandImage"]);
            self::add($name, $island);
            $count++;
        }
        AzSkyBlock::getInstance()->getLogger()->info("Loaded $count custom islands");
    }
}