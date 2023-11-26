<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\command\sub;

use CortexPE\Commando\BaseSubCommand;
use phuongaz\azskyblock\form\CustomForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class CustomSub extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if(!$sender instanceof Player) return;

        (new CustomForm($sender))->send();
    }

    public function getPermission() :string {
        return "azskyblock.command.custom";
    }
}