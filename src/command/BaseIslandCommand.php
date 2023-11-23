<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\command;

use CortexPE\Commando\BaseCommand;
use phuongaz\azskyblock\form\SkyblockForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class BaseIslandCommand extends BaseCommand {

    protected function prepare(): void{
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if(!$sender instanceof Player) return;
        (new SkyblockForm($sender))->send($sender);
    }

    public function getPermission(): string {
        return "azskyblock.command";
    }
}