<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\handler;

use phuongaz\azskyblock\form\SkyblockForm;
use phuongaz\azskyblock\island\Island;
use phuongaz\azskyblock\world\WorldUtils;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\world\Position;
use SOFe\AwaitGenerator\Await;

class EventHandler implements Listener {

    public function onJoin(PlayerJoinEvent $event) :void {
        $player = $event->getPlayer();
        if(!$player->hasPlayedBefore()) {
            (new SkyblockForm($player))->send();
        }
    }

    public function onBreak(BlockBreakEvent $event) : void {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $this->handleCheck($block->getPosition(), $player, $event);
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
            $this->handleCheck($block->getPosition(), $player, $event);
        }
    }

    private function handleCheck(Position $position, Player $player, $event) :void {
        Await::g2c(WorldUtils::getIslandFromPos($position, function(?Island $island) use ($event, $player) {
            if(is_null($island)) {
                return;
            }
            if(!$island->canEdit($player)) {
                $player->sendMessage("§cYou can't break this block!");
                $event->cancel();
            }
        }));
    }
}