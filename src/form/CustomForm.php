<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\form;

use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Toggle;
use dktapps\pmforms\MenuOption;
use faz\common\form\AsyncForm;
use Generator;
use phuongaz\azskyblock\utils\LanguageUtils;
use phuongaz\azskyblock\world\custom\CustomIsland;
use phuongaz\azskyblock\world\custom\CustomPool;
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
            new Input("name", LanguageUtils::translate("menu.custom.create.name")),
            new Input("description", LanguageUtils::translate("menu.custom.create.description")),
        ];

        $response = yield from $this->custom(LanguageUtils::translate("menu.custom.create.title"), $elements);
        if ($response !== null) {
            $data = $response->getAll();
            $island = new CustomIsland($data["name"], $data["description"], "", $this->getPlayer()->getWorld());
            CustomPool::add($island);
            $this->sendMessage(LanguageUtils::translate("menu.custom.create.success", [$data["name"]]));
        }
    }

    public function edit(CustomIsland $island): \Generator {
        $elements = [
            new Input("name", LanguageUtils::translate("menu.custom.edit.name"), $island->getName(), $island->getName()),
            new Input("description", LanguageUtils::translate("menu.custom.edit.description"), $island->getDescription(), $island->getDescription()),
            new Toggle("spawn", $this->getToggleName($island->getSpawnPosition(), "spawn"), $island->getSpawnPosition() !== null),
            new Toggle("position1", $this->getToggleName($island->getPosition1(), "pos1"), $island->getPosition1() !== null),
            new Toggle("position2", $this->getToggleName($island->getPosition2(), "pos2"), $island->getPosition2() !== null),
        ];

        $response = yield from $this->custom(LanguageUtils::translate("menu.custom.edit.title"), $elements);
        if ($response !== null) {
            $this->processResponse($island, $response->getAll());
        }
    }

    private function getToggleName(?Position $position, string $content): string {
        return $position === null ? "§aSet " . $content : "Pos: ({$position->getFloorX()}, {$position->getFloorY()}, {$position->getFloorZ()})";
    }

    private function processResponse(CustomIsland $island, array $response): void {
        $name = $response["name"];
        $description = $response["description"];
        $island->setName($name);
        $island->setDescription($description);
        $this->sendMessage("Edited island $name");

        $this->handleToggleAction($island, $response, "spawn", LanguageUtils::translate("set.spawn.position"), 'setSpawnPosition', 'getSpawnPosition');
        $this->handleToggleAction($island, $response, "position1", LanguageUtils::translate("set.position.1"), 'setPosition1', 'getPosition1');
        $this->handleToggleAction($island, $response, "position2", LanguageUtils::translate("set.position.2"), 'setPosition2', 'getPosition2');

        if ($island->getSpawnPosition() !== null && $island->getPosition1() !== null && $island->getPosition2() !== null) {
            $island->save();
            $this->getPlayer()->sendMessage(LanguageUtils::translate("save.island"));
        }

        CustomPool::reload($island);
    }

    private function handleToggleAction(CustomIsland $island, array $response, string $toggleKey, string $message, string $setterMethod, string $getterMethod): void {
        if ($response[$toggleKey] && $island->$getterMethod() === null) {
            $island->$setterMethod($this->getPlayer()->getPosition());
            $this->sendMessage("$message ({$this->getPlayer()->getPosition()->getFloorX()}, {$this->getPlayer()->getPosition()->getFloorY()}, {$this->getPlayer()->getPosition()->getFloorZ()})");
        }
    }

    public function main(): Generator {
        $islands = CustomPool::getAll();

        if (empty($islands)) {
            yield from $this->create();
            return;
        }

        $elements = array_map(fn($island) => new MenuOption($island->getName()), $islands);
        $response = yield from $this->menu(LanguageUtils::translate("select.island"), LanguageUtils::translate("select.island.content"), $elements);

        if ($response !== null) {
            $selectedIsland = array_values($islands)[$response];
            yield from $this->edit($selectedIsland);
        }
    }
}