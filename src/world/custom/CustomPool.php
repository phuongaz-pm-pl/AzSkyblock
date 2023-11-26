<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\world\custom;

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

    public static function reload(CustomIsland $island) : void {
        self::remove($island->getName());
        self::add($island);
    }

    public function save() : void {
        foreach(self::$islands as $island) {
            $island->save();
        }
    }
}