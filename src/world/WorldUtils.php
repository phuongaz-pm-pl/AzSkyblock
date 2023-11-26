<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\world;

use Closure;
use Generator;
use phuongaz\azskyblock\AzSkyBlock;
use phuongaz\azskyblock\island\Island;
use phuongaz\azskyblock\utils\IslandSettings;
use pocketmine\Server;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\world\WorldCreationOptions;
use SOFe\AwaitGenerator\Await;

use czechpmdevs\multiworld\util\WorldUtils as MultiWorldUtils;

class WorldUtils {

    public static function createWorld(string $name) : void {
        $worldManager = Server::getInstance()->getWorldManager();
        if($worldManager->getWorldByName($name) !== null) {
            return;
        }
        $generator = GeneratorManager::getInstance()->getGenerator("flat")->getGeneratorClass();
        $worldCreator = WorldCreationOptions::create()->setGeneratorOptions("3;minecraft:air");
        $worldCreator->setGeneratorClass($generator);

        $worldManager->generateWorld($name, $worldCreator);
    }

    public static function getSkyBlockWorld() : ?World {
        $worldName = IslandSettings::getConfig()->get("world");
        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);

        if($world !== null) {
            return $world;
        } else {
            self::createWorld($worldName);
            return MultiWorldUtils::getLoadedWorldByName($worldName);
        }
    }

    public static function getIslandFromPos(Position $position, ?\Closure $closure = null) : Generator {
        $world = IslandSettings::getConfig()->get("world");

        if(!$position->getWorld()->getFolderName() === $world) {
            return null;
        }

        /** @var Island|null $islands*/
        $island = yield from Await::promise(function (Closure $resolve) use ($position) {
            $provider = AzSkyBlock::getInstance()->getProvider();
            yield from $provider->awaitGetAll(function(array $islands) use ($position, $resolve){
                $result = null;

                /** @var Island $island*/
                foreach($islands as $island) {
                    $maxSize = IslandSettings::getMaxSize();
                    $spawn = $island->getIslandSpawn();

                    $x1 = $spawn->getFloorX() - $maxSize;
                    $x2 = $spawn->getFloorX() + $maxSize;
                    $z1 = $spawn->getFloorZ() - $maxSize;
                    $z2 = $spawn->getFloorZ() + $maxSize;
                    if($position->getFloorX() >= $x1 && $position->getFloorX() <= $x2 && $position->getFloorZ() >= $z1 && $position->getFloorZ() <= $z2) {
                        $result = $island;
                    }
                }
                $resolve($result);
            });
        });

        if($island === null) {
            return null;
        }

        if($closure !== null) {
            $closure($island);
        }

        return $island;
    }
}