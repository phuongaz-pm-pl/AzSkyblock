<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\command;

use CortexPE\Commando\BaseCommand;
use phuongaz\azskyblock\AzSkyBlock;
use phuongaz\azskyblock\command\sub\CustomSub;
use phuongaz\azskyblock\form\SkyblockForm;
use phuongaz\azskyblock\island\Island;
use phuongaz\azskyblock\world\WorldUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\Position;
use SOFe\AwaitGenerator\Await;

class BaseIslandCommand extends BaseCommand {

    protected function prepare(): void{
        $this->setPermission($this->getPermission());
        $this->registerSubCommand(new CustomSub("custom", "Custom command"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if(!$sender instanceof Player) {
            $this->testGetAll();
            return;
        }
        (new SkyblockForm($sender))->send();
    }

    private function testGetAll() : void {
        $plugin = AzSkyBlock::getInstance();
        $provider = $plugin->getProvider();
        Await::f2c(function() use ($provider) {
            /**@var $islands Island[]*/
            yield $provider->awaitGetAll(function($islands) {
                array_map(function($island) {
                    /** @var $island Island */
                    //var_dump($island->getIslandRange());
                }, $islands);
            });

            $world = WorldUtils::getSkyBlockWorld();
            $testPos = new Position(600, 0, 600, $world);
            yield WorldUtils::getIslandFromPos($testPos, function(?Island $island) {
                if(is_null($island)) {
                    var_dump("null");
                    return;
                }
                var_dump($island->getIslandName());
                var_dump($island->getIslandRange());
            });
        });
    }

    public function getPermission(): string {
        return "azskyblock.command";
    }
}