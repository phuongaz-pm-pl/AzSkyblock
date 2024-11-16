<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\island;

use phuongaz\azskyblock\AzSkyBlock;
use phuongaz\azskyblock\handler\island\IslandMemberEvent;
use phuongaz\azskyblock\handler\island\IslandTeleportEvent;
use phuongaz\azskyblock\handler\island\warp\IslandAddWarpEvent;
use phuongaz\azskyblock\handler\island\warp\IslandRemoveWarpEvent;
use phuongaz\azskyblock\island\components\Area;
use phuongaz\azskyblock\island\components\Level;
use phuongaz\azskyblock\island\components\types\Levels;
use phuongaz\azskyblock\island\components\Warp;
use phuongaz\azskyblock\utils\IslandSettings;
use phuongaz\azskyblock\world\WorldUtils;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\Position;
use SOFe\AwaitGenerator\Await;

class Island {

    private string $player;
    private string $islandName;
    private Level $islandLevel;
    private string $islandMembers;

    private Area $area;

    /** @var Warp[] $islandWarps */
    private array $islandWarps;

    private bool $islandLocked;

    private string $dateCreated;


    public function __construct(string $player, string $islandName, Level $islandLevel, string $islandMembers, Area $area, array $islandWarps, bool $islandLocked) {
        $this->player = $player;
        $this->islandName = $islandName;
        $this->islandLevel = $islandLevel;
        $this->islandMembers = $islandMembers;
        $this->area = $area;
        $this->islandWarps = $islandWarps;
        $this->islandLocked = $islandLocked;
    }

    public function getPlayer() : string {
        return $this->player;
    }

    public function getIslandName() : string {
        return $this->islandName;
    }

    public function getDateCreated() : string {
        return $this->dateCreated;
    }

    public function setDateCreated(string $dateCreated) : void {
        $this->dateCreated = $dateCreated;
    }

    public function getIslandLevel() : Level {
        return $this->islandLevel;
    }

    public function getIslandMembers() : string {
        return $this->islandMembers;
    }

    public function getIslandSpawn() : Position {
        return $this->area->getSpawn();
    }

    public function getIslandSpawnPosition() : Position {
        return $this->area->getSpawn();
    }

    /**
     * @return Warp[]
     */
    public function getIslandWarps() : array {
        return $this->islandWarps;
    }

    public function getIslandLocked() : bool {
        return $this->islandLocked;
    }

    public function setIslandName(string $islandName) : void {
        $this->islandName = $islandName;
    }

    public function setIslandLevel(Level $islandLevel) : void {
        $this->islandLevel = $islandLevel;
    }

    public function setIslandMembers(string $islandMembers) : void {
        $this->islandMembers = $islandMembers;
    }

    public function setIslandSpawn(Position $islandSpawn) : void {
        $this->area->setSpawn($islandSpawn);
    }

    public function setIslandWarps(array $islandWarps) : void {
        $this->islandWarps = $islandWarps;
    }

    public function setIslandLocked(bool $islandLocked) : void {
        $this->islandLocked = $islandLocked;
    }

    public function addWarp(string $warpName, Vector3 $warpPosition, bool $save = false) : bool {
        $warp = new Warp($warpName, $warpPosition);

        if($this->hasWarp($warpName)) {
            return false;
        }

        $event = new IslandAddWarpEvent($this, $warp);
        if($event->isCancelled()) {
            return false;
        }
        $event->call();
        $this->islandWarps[] = $warp;

        if($save) {
            $this->save();
        }

        return true;
    }

    public function removeWarp(string $warpName, bool $save = false) : bool {
        $warp = $this->getWarp($warpName);

        if(is_null($warp)) {
            return false;
        }

        $event = new IslandRemoveWarpEvent($this, $warp);
        if($event->isCancelled()) {
            return false;
        }
        $event->call();
        foreach ($this->islandWarps as $key => $warp) {
            if ($warp->getWarpName() == $warpName) {
                unset($this->islandWarps[$key]);
            }
        }

        if($save) {
            $this->save();
        }

        return true;
    }

    public function getWarp(string $warpName) : ?Warp {
        foreach ($this->islandWarps as $warp) {
            if ($warp->getWarpName() == $warpName) {
                return $warp;
            }
        }
        return null;
    }

    public function hasWarp(string $warpName) : bool {
        foreach ($this->islandWarps as $warp) {
            if ($warp->getWarpName() == $warpName) {
                return true;
            }
        }
        return false;
    }

    public function getWarpNames() : array {
        $warpNames = [];
        foreach ($this->islandWarps as $warp) {
            $warpNames[] = $warp->getWarpName();
        }
        return $warpNames;
    }

    public function addMember(string $member) : void {
        $event = new IslandMemberEvent($member, IslandMemberEvent::ADD, $this);
        if ($event->isCancelled()) {
            return;
        }
        $this->islandMembers .= "," . $member;
        $event->call();
        $this->save();
    }

    public function removeMember(string $member) : void {
        $event = new IslandMemberEvent($member, IslandMemberEvent::REMOVE, $this);
        if ($event->isCancelled()) {
            return;
        }
        $members = explode(",", $this->islandMembers);
        foreach ($members as $key => $m) {
            if ($m == $member) {
                unset($members[$key]);
            }
        }
        $this->islandMembers = implode(",", $members);
        $event->call();
        $this->save();
    }

    public function hasMember(string $member) : bool {
        $members = explode(",", $this->islandMembers);
        if (in_array($member, $members)) {
            return true;
        }
        return false;
    }

    public function getMembers() : array {
        return explode(",", $this->islandMembers);
    }

    public function getMemberCount() : int {
        return count(explode(",", $this->islandMembers));
    }

    public function isMember(string $member) : bool {
        return $this->player == $member || $this->hasMember($member);
    }

    public function isOwner(string $member) : bool {
        return $this->player == $member;
    }

    public function isLocked() : bool {
        return $this->islandLocked;
    }

    public function setLocked(bool $locked) : void {
        $this->islandLocked = $locked;
    }

    public function getArea() : Area {
        return $this->area;
    }

    public function canEdit(Player $player) : bool {
        if ($this->isOwner($player->getName()) || $this->isMember($player->getName())) {
            return true;
        }
        return false;
    }

    public function getPlayersInIsland() : array {
        $islandSize = IslandSettings::getMaxSize();
        $spawn = $this->getIslandSpawn();
        $x1 = $spawn->getFloorX() - $islandSize;
        $x2 = $spawn->getFloorX() + $islandSize;
        $z1 = $spawn->getFloorZ() - $islandSize;
        $z2 = $spawn->getFloorZ() + $islandSize;
        $world = WorldUtils::getSkyBlockWorld();

        $players = [];
        foreach ($world->getPlayers() as $player) {
            $playerPosition = $player->getPosition();
            if ($playerPosition->getFloorX() >= $x1 && $playerPosition->getFloorX() <= $x2 && $playerPosition->getFloorZ() >= $z1 && $playerPosition->getFloorZ() <= $z2) {
                $players[] = $player;
            }
        }

        return $players;
    }

    public static function fromArray(array $array) : Island {
        $islandWarps = [];
        foreach ($array["island_warps"] as $warp) {
            $islandWarps[] = Warp::fromArray($warp);
        }
        return new Island(
            $array["player"],
            $array["island_name"],
            Level::fromArray($array["island_level"]),
            $array["island_members"],
            new Area(
                new Position($array['area']["island_spawn_x"], $array['area']["island_spawn_y"], $array['area']["island_spawn_z"], WorldUtils::getSkyBlockWorld()),
                new Position($array['area']["island_min_x"] - IslandSettings::getMaxSize(), 0, $array['area']["island_min_z"] - IslandSettings::getMaxSize(), WorldUtils::getSkyBlockWorld()),
                new Position($array['area']["island_max_x"] + IslandSettings::getMaxSize(), 0, $array['area']["island_min_z"] + IslandSettings::getMaxSize(), WorldUtils::getSkyBlockWorld())
            ),
            $islandWarps,
            $array["island_locked"]
        );
    }

    public function toArray() : array {
        $islandWarps = [];
        foreach ($this->islandWarps as $warp) {
            $islandWarps[] = $warp->toArray();
        }
        return [
            "player" => $this->player,
            "island_name" => $this->islandName,
            "island_level" => $this->islandLevel->toArray(),
            "island_members" => $this->islandMembers,
            "area" => [
                "island_spawn_x" => $this->area->getSpawn()->getX(),
                "island_spawn_y" => $this->area->getSpawn()->getY(),
                "island_spawn_z" => $this->area->getSpawn()->getZ(),
                "island_min_x" => $this->area->getMin()->getX(),
                "island_min_z" => $this->area->getMin()->getZ(),
                "island_max_x" => $this->area->getMax()->getX(),
                "island_max_z" => $this->area->getMax()->getZ()
            ],
            "island_spawn_world" => WorldUtils::getSkyBlockWorld()->getFolderName(),
            "island_warps" => $islandWarps,
            "island_locked" => $this->islandLocked
        ];
    }

    public function teleportToIsland(Player $player) : void {
        $player->teleport($this->getIslandSpawnPosition());
    }

    public static function new(string $player, string $islandName, Area $area) : Island {
        return new Island(
            $player,
            $islandName,
            new Level(Levels::LEVEL_1, 0),
            $player,
            $area,
            [],
            false
        );
    }

    public function save() : void {
        $provider = AzSkyBlock::getInstance()->getProvider();
        Await::g2c($provider->awaitUpdate($this->getPlayer(), $this));
    }

    /**
     * @return array[Pos1: Position, Pos2: Position, Spawn: Position]
    */
    public function getIslandRange() : array {
        return [
            "Pos1" => $this->area->getMin(),
            "Pos2" => $this->area->getMax(),
            "Spawn" => $this->area->getSpawn()
        ];
    }

    public function teleport(Player $player) : void {
        $event = new IslandTeleportEvent($player->getName(), $this);
        $event->call();

        if($event->isCancelled()) {
            return;
        }

        $this->teleportToIsland($player);
    }
}