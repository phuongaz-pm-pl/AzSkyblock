<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\island\components;

use phuongaz\azskyblock\world\WorldUtils;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class Warp {

    public function __construct(
        private string $warpName,
        private Vector3 $warpPosition
    ){}

    public function getWarpName() : string {
        return $this->warpName;
    }

    public function getWarpPosition() : Position {
        $world = WorldUtils::getSkyBlockWorld();
        return new Position(
            $this->warpPosition->getX(),
            $this->warpPosition->getY(),
            $this->warpPosition->getZ(),
            $world
        );
    }

    public function setWarpName(string $warpName) : void {
        $this->warpName = $warpName;
    }

    public static function fromArray(array $array) : Warp {
        return new Warp(
            $array["warp_name"],
            new Vector3(
                $array["warp_x"],
                $array["warp_y"],
                $array["warp_z"]
            )
        );
    }

    public function toArray() : array {
        return [
            "warp_name" => $this->warpName,
            "warp_x" => $this->warpPosition->getX(),
            "warp_y" => $this->warpPosition->getY(),
            "warp_z" => $this->warpPosition->getZ()
        ];
    }
}