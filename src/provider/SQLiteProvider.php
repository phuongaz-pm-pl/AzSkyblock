<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\provider;

use Closure;
use Generator;
use phuongaz\azskyblock\island\Island;
use poggit\libasynql\DataConnector;

class SQLiteProvider {

    const INIT = "azskyblock_island.init";
    const CREATE = "azskyblock_island.insert";
    const DELETE = "azskyblock_island.delete";
    const UPDATE = "azskyblock_island.update";
    const GET = "azskyblock_island.select";
    const GET_ALL = "azskyblock_island.selects";
    const COUNT = "azskyblock_island.count";

    public function __construct(
        private DataConnector $connector
    ) {
        $this->connector->executeGeneric(self::INIT);
    }

    public function getConnector() : DataConnector {
        return $this->connector;
    }


    public function awaitCreate(string $player, Island $island, ?Closure $closure = null) : Generator {
        yield from $this->connector->asyncInsert(self::CREATE, [
            "player" => $player,
            "data" => json_encode($island->toArray()),
            "created_at" => date("Y-m-d H:i:s")
        ]);
        $this->handleClosure($closure, null);
    }

    public function awaitDelete(string $player, ?Closure $closure = null) : Generator {
        yield from $this->connector->asyncChange(self::DELETE, [
            "player" => $player
        ]);
        $this->handleClosure($closure, null);
    }

    public function awaitUpdate(string $player, Island $island, ?Closure $closure = null) : Generator {
        yield from $this->connector->asyncChange(self::UPDATE, [
            "player" => $player,
            "data" => json_encode($island->toArray())
        ]);
        $this->handleClosure($closure, null);
    }

    public function awaitGet(string $player, ?Closure $closure = null) : Generator {
        $result = yield from $this->connector->asyncSelect(self::GET, [
            "player" => $player
        ]);

        if(!empty($result)) {
            $island = Island::fromArray(json_decode($result["data"], true));
            $this->handleClosure($closure, $island);
            return;
        }
        $this->handleClosure($closure, null);
    }

    public function awaitGetAll(?Closure $closure = null) : Generator {
        $result = yield from $this->connector->asyncSelect(self::GET_ALL);

        $islands = [];
        foreach($result as $data) {
            $islands[] = Island::fromArray(json_decode($data["data"], true));
        }

        $this->handleClosure($closure, $islands);
    }

    public function awaitCount(?Closure $closure = null) : Generator {
        $result = yield from $this->connector->asyncSelect(self::COUNT);
        $this->handleClosure($closure, $result);
    }

    private function handleClosure(?Closure $closure, $result) : void {
        if($closure !== null) {
            $closure($result);
        }
    }

}
