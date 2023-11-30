<?php

declare(strict_types=1);

namespace phuongaz\azskyblock;

use phuongaz\azskyblock\command\BaseIslandCommand;
use phuongaz\azskyblock\handler\EventHandler;
use phuongaz\azskyblock\provider\SQLiteProvider;
use phuongaz\azskyblock\utils\LanguageUtils;
use phuongaz\azskyblock\world\custom\IslandPool;
use phuongaz\azskyblock\world\WorldUtils;
use pocketmine\lang\Language;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\libasynql;

class AzSkyBlock extends PluginBase {
    use SingletonTrait;

    private Language $language;

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
        $this->initLanguage();
        Server::getInstance()->getCommandMap()->register("AzSkyBlock", new BaseIslandCommand(
            $this, "azskyblock", "AzSkyBlock command", ["azsb"]
        ));
        WorldUtils::createWorld($this->getConfig()->get("world"));
        IslandPool::loads();
    }

    public function getProvider() : SQLiteProvider {
        return $this->provider;
    }

    public function getLanguage() : Language {
        return $this->language;
    }

    private function initLanguage(): void {
        $defaultLanguage = "eng";
        $languageCode = $this->getConfig()->get("language", $defaultLanguage);
        $languagesFolder = $this->getFile() . "resources/languages/";

        if (!file_exists($languagesFolder . $languageCode . ".ini")) {
            $this->getLogger()->warning("Language $languageCode not found, using default language");
            $languageCode = $defaultLanguage;
        }

        $this->language = new Language($languageCode, $languagesFolder);
    }

    private function generateDefaultIslands() : void {
        if(!is_dir($this->getDataFolder() . "islands/")) {
            mkdir($this->getDataFolder() . "islands/");
            $this->saveResource("islands/basic-island.json");
        }
    }
}