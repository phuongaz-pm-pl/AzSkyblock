<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\world\custom;

use faz\common\Debug;
use InvalidArgumentException;
use phuongaz\azskyblock\AzSkyBlock;
use phuongaz\azskyblock\island\components\Area;
use phuongaz\azskyblock\utils\IslandSettings;
use phuongaz\azskyblock\utils\Util;
use phuongaz\azskyblock\world\WorldUtils;
use pocketmine\block\Chest;
use pocketmine\block\tile\Chest as TileChest;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;
use pocketmine\world\World;
use SOFe\AwaitGenerator\Await;

class CustomIsland {

    /**
     * @param array{vector: Vector3, block: int} $blocks
     */
    public function __construct(
        private string $name,
        private string $description,
        private string $islandImage,
        private World $world,
        private ?Vector3 $spawnPosition = null,
        private ?Vector3 $position1 = null,
        private ?Vector3 $position2 = null,
        private array $blocks = [],
    ){
        if(empty($this->blocks) and $this->spawnPosition !== null and $this->position1 !== null and $this->position2 !== null) {
            $this->parseBlocks();
        }
    }

    public function getName() : string {
        return $this->name;
    }

    public function getDescription() : string {
        return $this->description;
    }

    public function getIslandImage() : string {
        return $this->islandImage;
    }

    public function setDescription(string $description) : void {
        $this->description = $description;
    }

    public function getSpawnPosition() : ?Vector3 {
        return $this->spawnPosition;
    }

    public function getPosition1() : ?Vector3 {
        return $this->position1;
    }

    public function getPosition2() : ?Vector3 {
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
        $minX = min($this->position1->getFloorX(), $this->position2->getFloorX());
        $maxX = max($this->position1->getFloorX(), $this->position2->getFloorX());
        $minY = min($this->position1->getFloorY(), $this->position2->getFloorY());
        $maxY = max($this->position1->getFloorY(), $this->position2->getFloorY());
        $minZ = min($this->position1->getFloorZ(), $this->position2->getFloorZ());
        $maxZ = max($this->position1->getFloorZ(), $this->position2->getFloorZ());
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
            "description" => $this->description,
            "island_image" => $this->islandImage,
            "world" => $this->world->getFolderName(),
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
            "blocks" => base64_encode(serialize($this->blocks)),
        ];
    }

    public static function fromArray(array $array) : CustomIsland {
        return new CustomIsland(
            $array["name"],
            $array["description"],
            $array["island_image"],
            Server::getInstance()->getWorldManager()->getWorldByName($array["world"]),
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
            unserialize(base64_decode($array["blocks"])),
        );
    }

    public function generate(?\Closure $closure = null) : void {
        Await::f2c(function() use ($closure){

            $provider = AzSkyBlock::getInstance()->getProvider();
            yield $provider->awaitCount(function(int $distance) use ($closure){

                $blocks = $this->getBlocks();
                $world = WorldUtils::getSkyBlockWorld();
                $chests = [];
                $chestTiles = [];

                $pos1 = $this->position1;
                $pos2 = $this->position2;

                $minX = min($pos1->getFloorX(), $pos2->getFloorX());
                $maxX = max($pos1->getFloorX(), $pos2->getFloorX());
                $minY = min($pos1->getFloorY(), $pos2->getFloorY());
                $maxY = max($pos1->getFloorY(), $pos2->getFloorY());
                $minZ = min($pos1->getFloorZ(), $pos2->getFloorZ());
                $maxZ = max($pos1->getFloorZ(), $pos2->getFloorZ());

                $maxSize = IslandSettings::getMaxSize();
                //10 is space between islands (10^2)
                $initSize = $maxSize - 10;
                $counter = 0;

                $islandOffsetX = $distance * $maxSize;
                $islandOffsetZ = $distance * $maxSize;

                for ($x = $islandOffsetX; $x <= $islandOffsetX + ($maxX - $minX); $x++) {
                    for ($y = 15; $y <= 15 + ($maxY - $minY); ++$y) {
                        for ($z = $islandOffsetZ; $z <= $islandOffsetZ + ($maxZ - $minZ); $z++) {
                            $block = RuntimeBlockStateRegistry::getInstance()->fromStateId($blocks[$counter]["block"]);
                            try {
                                $vector = $blocks[$counter]["vector"];
                                $vector = $vector->add($islandOffsetX, 0, $islandOffsetZ);
                                $chunkX = $vector->getFloorX() >> Chunk::COORD_BIT_SIZE;
                                $chunkZ = $vector->getFloorZ() >> Chunk::COORD_BIT_SIZE;
                                $world->orderChunkPopulation($chunkX, $chunkZ, null)->onCompletion(function(Chunk $chunk) use ($block, $vector, $world) {
                                    $world->setBlockAt($vector->getFloorX(), $vector->getFloorY(), $vector->getFloorZ(), $block);
                                }, fn() => null);

                                if($block instanceof Chest) {
                                    $chests[] = $world->getBlockAt($vector->getFloorX(), $vector->getFloorY(), $vector->getFloorZ());
                                }
                            } catch (InvalidArgumentException $exception) {
                                Debug::dump("Invalid block at " . $vector->getFloorX() . " " . $vector->getFloorY() . " " . $vector->getFloorZ());
                            }
                            $counter++;
                        }
                    }
                }

                $hasGiven = false;
                $startItems = IslandSettings::getStartItems();

                foreach($chests as $chest) {
                    $tile = $world->getTile($chest->getPosition());
                    if($tile instanceof TileChest) {
                        $chestTiles[] = $tile;
                    }
                }

                $chestCount = count($chestTiles);
                if ($chestCount > 0) {

                    $itemCount = count($startItems);
                    foreach ($chestTiles as $tile) {
                        if($itemCount <= 0) {
                            continue;
                        }
                        shuffle($startItems);
                        $random = mt_rand(1, $itemCount);

                        for ($i = 0; $i < $random; ++$i) {
                            if (!empty($startItems)) {
                                $item = array_shift($startItems);
                                $tile->getInventory()->addItem($item);
                                $itemCount--;
                            }
                        }
                    }
                    $hasGiven = true;
                }

                $startX = ($distance * $maxSize + $pos1->getX()) - ($initSize / 2);
                $startZ = ($distance * $maxSize + $pos1->getZ()) - ($initSize / 2) ;

                $endX = ($distance * $maxSize + $pos2->getX()) + ($initSize / 2);
                $endZ = ($distance * $maxSize + $pos2->getZ()) + ($initSize / 2);


                $maxIslandArea = new Position($endX, $pos1->getY(), $endZ, $world);
                $minIslandArea = new Position($startX, $pos1->getY(), $startZ, $world);
                $spawn = new Position($distance * $maxSize + $this->spawnPosition->getX(), $this->spawnPosition->getY(), $distance * $maxSize + $this->spawnPosition->getZ(), $world);

                $area = new Area($spawn, $minIslandArea, $maxIslandArea);

                if($closure !== null) {
                    $closure($area, $hasGiven);
                }
            });
        });
    }

    public function save() : void {
        if(!file_exists(AzSkyBlock::getInstance()->getDataFolder() . "islands")) {
            mkdir(AzSkyBlock::getInstance()->getDataFolder() . "islands");
        }

        if(empty($this->blocks) and $this->spawnPosition !== null and $this->position1 !== null and $this->position2 !== null) {
            $this->parseBlocks();
        }

        $file = AzSkyBlock::getInstance()->getDataFolder() . "islands" . DIRECTORY_SEPARATOR . Util::convertToSlug($this->name) . ".json";
        file_put_contents($file, json_encode($this->toArray()));

        IslandPool::add($this->name, $this);
    }
}