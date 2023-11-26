<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\world\custom;

use phuongaz\azskyblock\world\CustomIsland;

class CustomPool {

    private static $islands = [];

    public static function add(CustomIsland $island) : void {
        self::$islands[$island->getName()] = $island;
    }

    public static function get(string $name) : ?CustomIsland {
        return self::$islands[$name] ?? null;
    }

    public static function remove(string $name) : void {
        unset(self::$islands[$name]);
    }

    public static function getAll() : array {
        return self::$islands;
    }

    public function save() : void {
        foreach(self::$islands as $island) {
            $island->save();
        }
    }
}