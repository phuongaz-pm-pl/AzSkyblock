<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\form;

use faz\common\form\AsyncForm;
use phuongaz\azskyblock\island\Island;
use pocketmine\player\Player;

class InvitedForm extends AsyncForm {

    public function __construct(Player $player, private string $content, private Island $island, private \Closure $closure) {
        parent::__construct($player);
    }

    public function main(): \Generator {
        $accept = yield from $this->modal(
            "Invited",
            $this->content,
            "Accept",
            "Decline");

        if($accept) {
            ($this->closure)($this->island);
        }
    }
}