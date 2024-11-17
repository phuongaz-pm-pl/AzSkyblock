<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\handler;

use phuongaz\azskyblock\AzSkyBlock;
use phuongaz\azskyblock\AZSkyblockAPI;
use phuongaz\azskyblock\form\SkyblockForm;
use phuongaz\azskyblock\handler\island\IslandTeleportEvent;
use phuongaz\azskyblock\handler\island\member\IslandMemberEvent;
use phuongaz\azskyblock\island\Island;
use phuongaz\azskyblock\provider\cache\Cache;
use phuongaz\azskyblock\world\WorldUtils;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use SOFe\AwaitGenerator\Await;

class EventHandler implements Listener {

    public function onJoin(PlayerJoinEvent $event) :void {
        $player = $event->getPlayer();
        $provider = AzSkyBlock::getInstance()->getProvider();
        Await::g2c($provider->awaitGet($player->getName(), function(?Island $island) use ($player) {
            if(is_null($island)) {
                (new SkyblockForm($player))->send();
                return;
            }
            $island->teleport($player);
        }));
    }

    public function onBreak(BlockBreakEvent $event) : void {
        $player = $event->getPlayer();
        $this->handleCheck($event->getBlock()->getPosition(), $player, $event);
    }

    public function onTouch(PlayerInteractEvent $event) : void {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $this->handleCheck($block->getPosition(), $player, $event);
    }

    public function onBucket(PlayerBucketEvent $event) : void {
        $player = $event->getPlayer();
        $block = $event->getBlockClicked();
        $this->handleCheck($block->getPosition(), $player, $event);
    }

    public function onPlace(BlockPlaceEvent $event) : void {
        $player = $event->getPlayer();
        foreach($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]){
            $this->handleCheck($block->getPosition(), $player, $event );
        }
    }

    public function onIslandTeleport(IslandTeleportEvent $event) {
        Cache::loadIsland($event->getIsland()->getPlayer());
    }

    public function onIslandMemberChange(IslandMemberEvent $event) {
        Cache::reloadIsland($event->getIsland()->getPlayer());
    }

    private function handleCheck(Position $position, Player $player, $event) :void {
        $island = AZSkyblockAPI::getIslandFromPos($position);

        if(!$island?->canEdit($player) || $island == null) {
            $player->sendMessage("§cYou can't access this area!, you are not the owner or member of this island!");
            $event->cancel();
        }
    }
}