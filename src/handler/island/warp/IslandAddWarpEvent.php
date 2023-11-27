<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\handler\island\warp;

use phuongaz\azskyblock\handler\island\IslandEvent;
use phuongaz\azskyblock\island\components\Warp;
use phuongaz\azskyblock\island\Island;
use pocketmine\event\CancellableTrait;

class IslandAddWarpEvent extends IslandEvent {
    use CancellableTrait;

    public function __construct(Island $island, private Warp $warp) {
        parent::__construct($island);
    }

    public function getWarp() : Warp {
        return $this->warp;
    }
}