<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\handler\island;

use phuongaz\azskyblock\island\Island;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

abstract class IslandEvent extends Event {
    use CancellableTrait;

    public function __construct(
        private Island $island
    ){}
   public function getIsland() : Island {
        return $this->island;
    }
}