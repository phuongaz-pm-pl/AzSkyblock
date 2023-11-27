<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\island\components\types;

enum Levels: int {
    case LEVEL_1 = 0;
    case LEVEL_2 = 50;
    case LEVEL_3 = 125;
    case LEVEL_4 = 250;
    case LEVEL_5 = 500;
    case LEVEL_6 = 1000;
    case LEVEL_7 = 2000;
    case LEVEL_8 = 4000;
    case LEVEL_9 = 8000;

    public function getNextExp() : int {
        return match($this) {
            self::LEVEL_1 => self::LEVEL_2,
            self::LEVEL_2 => self::LEVEL_3,
            self::LEVEL_3 => self::LEVEL_4,
            self::LEVEL_4 => self::LEVEL_5,
            self::LEVEL_5 => self::LEVEL_6,
            self::LEVEL_6 => self::LEVEL_7,
            self::LEVEL_7 => self::LEVEL_8,
            self::LEVEL_8 => self::LEVEL_9,
            self::LEVEL_9 => -1,
            default => 0
        };
    }

    public function isMaxLevel() : bool {
        return $this->value == self::LEVEL_9;
    }

    public function toLevel() : int {
        return match($this) {
            self::LEVEL_1 => 1,
            self::LEVEL_2 => 2,
            self::LEVEL_3 => 3,
            self::LEVEL_4 => 4,
            self::LEVEL_5 => 5,
            self::LEVEL_6 => 6,
            self::LEVEL_7 => 7,
            self::LEVEL_8 => 8,
            self::LEVEL_9 => 9
        };
    }


    public static function getLevel(int $level) : self {
        return match($level) {
            2 => self::LEVEL_2,
            3 => self::LEVEL_3,
            4 => self::LEVEL_4,
            5 => self::LEVEL_5,
            6 => self::LEVEL_6,
            7 => self::LEVEL_7,
            8 => self::LEVEL_8,
            9 => self::LEVEL_9,
            default => self::LEVEL_1
        };
    }

    public static function getExp(int $level) : int {
        return match($level) {
            1 => self::LEVEL_1,
            2 => self::LEVEL_2,
            3 => self::LEVEL_3,
            4 => self::LEVEL_4,
            5 => self::LEVEL_5,
            6 => self::LEVEL_6,
            7 => self::LEVEL_7,
            8 => self::LEVEL_8,
            9 => self::LEVEL_9,
            default => 0
        };
    }
}