<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\world;

use InvalidArgumentException;
use phuongaz\azskyblock\AzSkyBlock;
use phuongaz\azskyblock\utils\IslandSettings;
use phuongaz\azskyblock\utils\Util;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;
use SOFe\AwaitGenerator\Await;

class CustomIsland {

    /**
     * @param array{vector: Vector3, block: int} $blocks
     */
    public function __construct(
        private string $name,
        private Vector3 $spawnPosition,
        private Vector3 $position1,
        private Vector3 $position2,
        private World $world,
        private array $blocks = [],
        private string $description = "",
        private string $islandImage = ""
    ){
        if(empty($this->blocks)) {
            $this->parseBlocks();
        }
    }

    public function getName() : string {
        return $this->name;
    }

    public function getSpawnPosition() : Vector3 {
        return $this->spawnPosition;
    }

    public function getPosition1() : Vector3 {
        return $this->position1;
    }

    public function getPosition2() : Vector3 {
        return $this->position2;
    }

    public function getWorld() : World {
        return $this->world;
    }

    public function setName(string $name) : void {
        $this->name = $name;
    }

    public function setSpawnPosition(Vector3 $spawnPosition) : void {
        $this->spawnPosition = $spawnPosition;
    }

    public function setPosition1(Vector3 $position1) : void {
        $this->position1 = $position1;
    }

    public function setPosition2(Vector3 $position2) : void {
        $this->position2 = $position2;
    }

    public function setWorld(World $world) : void {
        $this->world = $world;
    }

    public function parseBlocks() : void {
        $minX = min($this->position1->getX(), $this->position2->getX());
        $maxX = max($this->position1->getX(), $this->position2->getX());
        $minY = min($this->position1->getY(), $this->position2->getY());
        $maxY = max($this->position1->getY(), $this->position2->getY());
        $minZ = min($this->position1->getZ(), $this->position2->getZ());
        $maxZ = max($this->position1->getZ(), $this->position2->getZ());
        for($x = $minX; $x <= $maxX; ++$x) {
            for($y = $minY; $y <= $maxY; ++$y) {
                for($z = $minZ; $z <= $maxZ; ++$z) {
                    $this->blocks[] = [
                        "vector" => new Vector3($x, $y, $z),
                        "block" => $this->world->getBlockAt($x, $y, $z)->getStateId()
                    ];
                }
            }
        }
    }

    public function getBlocks() : array {
        return $this->blocks;
    }

    public function toArray() : array {
        return [
            "name" => $this->name,
            "spawn_position" => [
                "x" => $this->spawnPosition->getX(),
                "y" => $this->spawnPosition->getY(),
                "z" => $this->spawnPosition->getZ()
            ],
            "position_1" => [
                "x" => $this->position1->getX(),
                "y" => $this->position1->getY(),
                "z" => $this->position1->getZ()
            ],
            "position_2" => [
                "x" => $this->position2->getX(),
                "y" => $this->position2->getY(),
                "z" => $this->position2->getZ()
            ],
            "world" => $this->world->getFolderName(),
            "blocks" => base64_encode(serialize($this->blocks)),
            "description" => $this->description,
            "island_image" => $this->islandImage
        ];
    }

    public static function fromArray(array $array) : CustomIsland {
        return new CustomIsland(
            $array["name"],
            new Vector3(
                $array["spawn_position"]["x"],
                $array["spawn_position"]["y"],
                $array["spawn_position"]["z"]
            ),
            new Vector3(
                $array["position_1"]["x"],
                $array["position_1"]["y"],
                $array["position_1"]["z"]
            ),
            new Vector3(
                $array["position_2"]["x"],
                $array["position_2"]["y"],
                $array["position_2"]["z"]
            ),
            Server::getInstance()->getWorldManager()->getWorldByName($array["world"]),
            unserialize(base64_decode($array["blocks"])),
            $array["description"],
            $array["island_image"]
        );
    }

    public function generate(Position $position, ?\Closure $closure = null) : void {
        Await::f2c(function() use ($position, $closure){
            $provider = AzSkyBlock::getInstance()->getProvider();
            yield from $provider->awaitCount(function(int $distance) use ($position, $closure){
                $blocks = $this->getBlocks();
                $world = $position->getWorld();
                $x = $position->getX();
                $y = $position->getY();
                $z = $position->getZ();
                $maxSize = IslandSettings::getMaxSize();

                $minX = $x - (($distance + 1) * $maxSize);
                $maxX = $x + (($distance + 1) * $maxSize);
                $minY = $y - (($distance + 1) * $maxSize);
                $maxY = $y + (($distance + 1) * $maxSize);
                $minZ = $z - (($distance + 1) * $maxSize);
                $maxZ = $z + (($distance + 1) * $maxSize);

                $counter = 0;
                $center = $distance * $maxSize;
                for($xx = $minX; $xx <= $maxX; ++$xx) {
                    for($yy = $minY; $yy <= $maxY; ++$yy) {
                        for($zz = $minZ; $zz <= $maxZ; ++$zz) {
                            if($xx >= $minX + $center && $xx <= $maxX - $center &&
                                $zz >= $minZ + $center && $zz <= $maxZ - $center) {
                                continue;
                            }
                            $block = RuntimeBlockStateRegistry::getInstance()->fromStateId($blocks[$counter]["block"]);
                            try {
                                $world->setBlock($blocks[$counter]["vector"], $block, false);
                            } catch (InvalidArgumentException $exception) {
                                //todo: Create new world
                                throw new InvalidArgumentException("Cannot create island in this world");
                            }
                            $counter++;
                        }
                    }
                }
                if($closure !== null) {
                    $closure($this->spawnPosition);
                }
            });
        });
    }

    public function save() : void {
        if(!file_exists(AzSkyBlock::getInstance()->getDataFolder() . "islands")) {
            mkdir(AzSkyBlock::getInstance()->getDataFolder() . "islands");
        }

        $file = AzSkyBlock::getInstance()->getDataFolder() . "islands" . DIRECTORY_SEPARATOR . Util::convertToSlug($this->name) . ".json";
        file_put_contents($file, json_encode($this->toArray()));
    }
}