<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\handler\island;

use phuongaz\azskyblock\island\Island;
use pocketmine\event\CancellableTrait;

class IslandTeleportEvent extends IslandEvent {
    private string $player;

    public function __construct(string $player, Island $island) {
        parent::__construct($island);
        $this->player = $player;
    }

    public function getPlayerName() : string {
        return $this->player;
    }

}