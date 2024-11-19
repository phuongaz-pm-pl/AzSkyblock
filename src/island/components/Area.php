<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\island\components;

use pocketmine\world\Position;

class Area {

    public function __construct(
        private Position $spawn,
        private Position $min,
        private Position $max
    ){}


    public function getSpawn() : Position {
        return $this->spawn;
    }

    public function setSpawn(Position $spawn) : void {
        $this->spawn = $spawn;
    }

    public function getMin() : Position {
        return $this->min;
    }

    public function getMax() : Position {
        return $this->max;
    }

    public function isInArea(Position $pos) : bool {
        $min = $this->min;
        $max = $this->max;

        return $pos->getX() >= $min->getX() && $pos->getX() <= $max->getX() && $pos->getZ() >= $min->getZ() && $pos->getZ() <= $max->getZ();
    }

    public function getIslandRange() : array {
        return [
            "min" => [
                "x" => $this->min->getX(),
                "z" => $this->min->getZ()
            ],
            "max" => [
                "x" => $this->max->getX(),
                "z" => $this->max->getZ()
            ]
        ];
    }
}