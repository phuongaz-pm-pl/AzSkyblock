<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\form;

use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Toggle;
use dktapps\pmforms\MenuOption;
use faz\common\form\AsyncForm;
use Generator;
use phuongaz\azskyblock\world\custom\CustomPool;
use phuongaz\azskyblock\world\CustomIsland;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\Position;

class CustomForm extends AsyncForm {

    public function __construct(Player $player) {
        parent::__construct($player);
    }

    private function sendMessage(string $message): void {
        $this->getPlayer()->sendMessage($message);
    }

    public function create(): \Generator {
        $elements = [
            new Input("name", "Name of island"),
            new Input("description", "Description of island")
        ];

        $response = yield from $this->custom("Create island", $elements);
        if ($response !== null) {
            $name = $response["name"];
            $description = $response["description"];
            $island = new CustomIsland($name, $description, "", $this->getPlayer()->getWorld(), null, null, null);
            CustomPool::add($island);
            $this->sendMessage("Created island $name");
        }
    }

    public function edit(CustomIsland $island): \Generator {
        $elements = [
            new Input("name", "Name of island", $island->getName()),
            new Input("description", "Description of island", $island->getDescription()),
            new Toggle("spawn", $this->getToggleName($island->getSpawnPosition()), $island->getSpawnPosition() !== null),
            new Toggle("position1", $this->getToggleName($island->getPosition1()), $island->getPosition1() !== null),
            new Toggle("position2", $this->getToggleName($island->getPosition2()), $island->getPosition2() !== null),
        ];

        $response = yield from $this->custom("Edit island", $elements);
        if ($response !== null) {
            $this->processResponse($island, $response->getAll());
        }
    }

    private function getToggleName(null|Position|Vector3 $position): string {
        return $position !== null ? "§aSet" : "Pos: (" . $position->getFloorX() . ", " . $position->getFloorY() . ", " . $position->getFloorZ() . ")";
    }

    private function processResponse(CustomIsland $island, array $response): void {
        $name = $response["name"];
        $description = $response["description"];
        $island->setName($name);
        $island->setDescription($description);
        $this->sendMessage("Edited island $name");

        $playerPosition = $this->getPlayer()->getPosition();
        $this->handleToggleAction($island, $response, "spawn", "Set spawn position", $playerPosition, 'setSpawnPosition');
        $this->handleToggleAction($island, $response, "position1", "Set position 1", $playerPosition, 'setPosition1');
        $this->handleToggleAction($island, $response, "position2", "Set position 2", $playerPosition, 'setPosition2');

        if($island->getSpawnPosition() !== null && $island->getPosition1() !== null && $island->getPosition2() !== null) {
            $island->save();
        }

        CustomPool::add($island);
    }

    private function handleToggleAction(CustomIsland $island, array $response, string $toggleKey, string $message, Position $position, string $setterMethod): void {
        if ($response[$toggleKey]) {
            $island->$setterMethod($position);
            $this->sendMessage("$message ({$position->getFloorX()}, {$position->getFloorY()}, {$position->getFloorZ()})");
        }
    }

    public function main(): Generator {
        $islands = CustomPool::getAll();

        if(empty($islands)) {
            yield from $this->create();
            return;
        }

        $elements = [];
        foreach($islands as $island) {
            $elements[] = new MenuOption($island->getName(), $island->getDescription());
        }

        $response = yield from $this->menu("Select island", "choose one!", $elements);

        if($response !== null) {
            $response = $response->getAll(); // int
            $island = array_values($islands)[$response];
            yield from $this->edit($island);
        }
    }
}