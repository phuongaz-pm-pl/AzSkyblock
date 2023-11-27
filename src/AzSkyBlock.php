<?php

declare(strict_types=1);

namespace phuongaz\azskyblock;

use phuongaz\azskyblock\command\BaseIslandCommand;
use phuongaz\azskyblock\handler\EventHandler;
use phuongaz\azskyblock\island\Island;
use phuongaz\azskyblock\provider\SQLiteProvider;
use phuongaz\azskyblock\world\custom\IslandPool;
use phuongaz\azskyblock\world\WorldUtils;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\libasynql;
use SOFe\AwaitGenerator\Await;

class AzSkyBlock extends PluginBase {
    use SingletonTrait;

    private SQLiteProvider $provider;

    public function onLoad() : void {
        self::setInstance($this);
    }

    public function onEnable(): void {
        $connector = libasynql::create($this, $this->getConfig()->get("database"), [
            "sqlite" => "sqlite.sql"
        ]);
        $this->provider = new SQLiteProvider($connector);
        $this->getServer()->getPluginManager()->registerEvents(new EventHandler(), $this);
        $this->generateDefaultIslands();
        Server::getInstance()->getCommandMap()->register("AzSkyBlock", new BaseIslandCommand(
            $this, "azskyblock", "AzSkyBlock command", ["azsb"]
        ));
        WorldUtils::createWorld($this->getConfig()->get("world"));
        IslandPool::loads();
    }

    public function getProvider() : SQLiteProvider {
        return $this->provider;
    }

    public function generateDefaultIslands() : void {
        if(!is_dir($this->getDataFolder() . "islands/")) {
            mkdir($this->getDataFolder() . "islands/");
            $this->saveResource("islands/basic-island.json");
        }
    }
}