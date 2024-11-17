<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\handler\island\member;

use phuongaz\azskyblock\handler\island\IslandEvent;
use phuongaz\azskyblock\island\Island;

class IslandMemberEvent extends IslandEvent {

    const ADD = "azskyblock_island_member.add";
    const REMOVE = "azskyblock_island_member.remove";

    public function __construct(
        private string $member,
        private string $type,
        Island $island,
    ){
        parent::__construct($island);
    }

    public function getType() : string {
        return $this->type;
    }

    public function getMember() : string {
        return $this->member;
    }
}