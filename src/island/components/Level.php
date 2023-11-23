<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\island\components;

use phuongaz\azskyblock\island\components\types\Levels;

class Level {

    public function __construct(
        private Levels $level,
        private int $exp
    ){}

    public function getLevel() : Levels {
        return $this->level;
    }

    public function getExp() : int {
        return $this->exp;
    }

    public function setLevel(int $level) : void {
        $this->level = Levels::from($level);
    }

    public function setExp(int $exp) : void {
        $this->exp = $exp;
    }

    public static function fromArray(array $array) : Level {
        return new Level(
            Levels::from($array["level"]),
            $array["exp"]
        );
    }

    public function toArray() : array {
        return [
            "level" => $this->level->toLevel(),
            "exp" => $this->exp
        ];
    }

}