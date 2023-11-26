<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\command;

use CortexPE\Commando\BaseCommand;
use phuongaz\azskyblock\command\sub\CustomSub;
use phuongaz\azskyblock\form\SkyblockForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class BaseIslandCommand extends BaseCommand {

    protected function prepare(): void{
        $this->setPermission($this->getPermission());
        $this->registerSubCommand(new CustomSub("custom", "Custom command"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if(!$sender instanceof Player) return;
        (new SkyblockForm($sender))->send();
    }

    public function getPermission(): string {
        return "azskyblock.command";
    }
}