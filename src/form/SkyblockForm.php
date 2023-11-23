<?php

declare(strict_types=1);

namespace phuongaz\azskyblock\form;

use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Label;
use dktapps\pmforms\element\Toggle;
use dktapps\pmforms\MenuOption;
use faz\common\form\AsyncForm;
use Generator;
use phuongaz\azskyblock\AzSkyBlock;
use phuongaz\azskyblock\island\Island;
use phuongaz\azskyblock\world\custom\IslandPool;
use phuongaz\azskyblock\world\CustomIsland;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use SOFe\AwaitGenerator\Await;

class SkyblockForm extends AsyncForm {

    public function __construct(Player $player) {
        parent::__construct($player);
    }

    public function main(): Generator {

        $provider = AzSkyBlock::getInstance()->getProvider();
        /** @var Island|null $playerIsland*/
        $playerIsland = yield from $provider->awaitGet($this->getPlayer()->getName(), function(?Island $island) {
            if(!is_null($island)) {
                Await::g2c($this->chooseIsland());
            }
        });

        if($playerIsland == null) {
            return;
        }

        $menuOptions = [
            new MenuOption("Teleport another"),
            new MenuOption("Manager"),
            new MenuOption("Settings"),
            new MenuOption("Warps")
        ];

        $menuChoose = yield from $this->menu(
            "Skyblock",
            $playerIsland->getIslandName(),
            $menuOptions
        );

        if($menuChoose === 0) {
            yield from $this->teleport($playerIsland);
        }
    }

    public function warps(Island $island) : Generator {
        $menuOptions = [
            new MenuOption("Create"),
            new MenuOption("Remove"),
            new MenuOption("Teleport")
        ];

        $menuChoose = yield from $this->menu(
            "Warps",
            $island->getIslandName(),
            $menuOptions
        );

        if($menuChoose === 0) {
            yield from $this->createWarp($island);
        }

        if($menuChoose === 1) {
            yield from $this->removeWarp($island);
        }

        if($menuChoose === 2) {
            yield from $this->teleportWarp($island);
        }
    }

    public function createWarp(Island $island) : Generator {
        $elements = [
            new Input("name", "Name of warp"),
        ];

        /** @var CustomFormResponse|null $response*/
        $response = yield from $this->custom("Create warp", $elements);
        if($response !== null) {
            $data = $response->getAll();
            $name = $data["name"];

            if($island->isInIsland($this->getPlayer())) {
                $this->getPlayer()->sendMessage("§cYou must be in your island");
                return;
            }

            $island->addWarp($name, $this->getPlayer()->getPosition());
            $this->getPlayer()->sendMessage("§aWarp " . $name . " has been created");
            return;
        }
        yield from $this->warps($island);
    }

    public function removeWarp(Island $island) : Generator {
        $warps = $island->getIslandWarps();
        $warpsName = array_map(function(string $warp) {
            return $warp;
        }, $warps);

        $response = yield from $this->custom("Remove warp", [
            new Label("label", "Warps of your island"),
            new Dropdown("name", "Name of warp", $warpsName)
        ]);

        if($response !== null) {
            $warpName = $response->getAll()["name"];
            $warp = array_values($warpsName)[$warpName];

            $island->removeWarp($warp);
            $this->getPlayer()->sendMessage("§aWarp " . $warp . " has been removed");
            return;
        }
        yield from $this->warps($island);
    }

    public function teleportWarp(Island $island) : Generator {
        $warps = $island->getIslandWarps();
        $warpsName = array_map(function(string $warp) {
            return $warp;
        }, $warps);

        $response = yield from $this->custom("Teleport warp", [
            new Label("label", "Warps of your island"),
            new Dropdown("name", "Name of warp", $warpsName)
        ]);

        if($response !== null) {
            $warpName = $response->getAll()["name"];
            $warpName = array_values($warpsName)[$warpName];
            $warp = $island->getWarp($warpName);
            $this->getPlayer()->teleport($warp->getWarpPosition());
            $this->getPlayer()->sendMessage("§aTeleported to " . $warpName);
            return;
        }
        yield from $this->warps($island);
    }

    public function teleport(Island $island) : Generator {
        $elements = [
            new Input("name", "Name of player", $island->getIslandName()),
        ];

        $provider = AzSkyBlock::getInstance()->getProvider();

        /** @var CustomFormResponse|null $response*/
        $response = yield from $this->custom("Teleport", $elements);
        if($response !== null) {
            $data = $response->getAll();
            $name = $data["name"];
            yield from $provider->awaitGet($name, function(?Island $island) {
                if(is_null($island)) {
                    $this->getPlayer()->sendMessage("§cPlayer not found");
                    return;
                }
                if($island->isLocked()) {
                    $this->getPlayer()->sendMessage("§cPlayer's island is locked");
                    return;
                }
                $spawn = $island->getIslandSpawn();
                $this->getPlayer()->teleport($spawn);
                $this->getPlayer()->sendMessage("§aTeleported to " . $island->getIslandName());
            });
            return;
        }
        yield from $this->main();
    }

    public function chooseIsland(): Generator {
        $islands = array_values(IslandPool::getAll());
        $menuOptions = [];

        foreach($islands as $island) {
            $menuOptions[] = new MenuOption($island->getIslandName());
        }

        $menuChoose = yield from $this->menu(
            "Choose island",
            "Choose island",
            $menuOptions
        );

        /** @var CustomIsland $island*/
        $island = array_values(IslandPool::getAll())[$menuChoose];

        $confirm = yield from $this->modal(
            "Confirm",
            "Create island " . $island->getName() . "?"
        );

        if($confirm) {
            $this->getPlayer()->sendMessage("§aCreating island " . $island->getName());
            $island->generate($this->getPlayer()->getPosition(), function(Position $spawn){
                $this->getPlayer()->sendMessage("Island created");

                $player = $this->getPlayer()->getName();
                $island = Island::new($player, $player . "'s island", $spawn);
                $provider = AzSkyBlock::getInstance()->getProvider();

                yield from $provider->awaitCreate($player, $island, function(?Island $island) {
                    $this->getPlayer()->teleport($island->getIslandSpawn());
                });
            });
            return;
        }
        yield from $this->chooseIsland();
    }

    public function manager(Island $island) : Generator {
        $menuOptions = [
            new MenuOption("Invite"),
            new MenuOption("Kick"),
            new MenuOption("Lock"),
            new MenuOption("Members"),
        ];

        $menuChoose = yield from $this->menu(
            "Manager",
            $island->getIslandName(),
            $menuOptions
        );

        if($menuChoose === 0) {
            yield from $this->inviteVisit($island);
        }

        if($menuChoose === 1) {
            yield from $this->kick($island);
        }

        if($menuChoose === 2) {
            yield from $this->lock($island);
        }

        if($menuChoose === 3) {
            yield from $this->members($island);
        }
    }

    public function members(Island $island) : Generator {
        $menuOptions = [
            new MenuOption("Add"),
            new MenuOption("Remove"),
        ];

        $menuChoose = yield from $this->menu(
            "Members",
            $island->getIslandName(),
            $menuOptions
        );

        if($menuChoose === 0) {
            yield from $this->addMember($island);
            return;
        }

        if($menuChoose === 1) {
            yield from $this->removeMembers($island);
            return;
        }
        yield from $this->manager($island);
    }

    public function addMember(Island $island) : Generator {
        $members = $island->getMembers();
        $membersName = array_map(function(string $member) {
            return $member;
        }, $members);

        $response = yield from $this->custom("Members", [
            new Label("label", "Members of your island"),
            new Dropdown("name", "Name of player", $membersName)
        ]);

        if($response !== null) {
            $playerName = $response->getAll()["name"];
            $player = array_values($membersName)[$playerName];

            if(($player = Server::getInstance()->getPlayerExact($player)) !== null) {
                $this->getPlayer()->sendMessage("§cPlayer is online");
                return;
            }
            (new InvitedForm($player,
                "Player " . $this->getPlayer()->getName() . " invited you to join island " . $island->getIslandName(),
                $island,
                function(bool $accept) use ($player, $island) {
                    if($accept) {
                        $island->addMember($player->getName());
                        $this->getPlayer()->sendMessage("§aPlayer accepted");
                    } else {
                        $this->getPlayer()->sendMessage("§cPlayer denied");
                    }
                }))->send();
        }
        yield from $this->members($island);
    }

    public function removeMembers(Island $island) : Generator {
        $members = $island->getMembers();
        $membersName = array_map(function(string $member) {
            return $member;
        }, $members);

        $response = yield from $this->custom("Members", [
            new Label("label", "Members of your island"),
            new Dropdown("name", "Name of player", $membersName)
        ]);

        if($response !== null) {
            $playerName = $response->getAll()["name"];
            $player = array_values($membersName)[$playerName];

            $island->removeMember($player);
            $this->getPlayer()->sendMessage("§aPlayer " . $player . " has been removed");
            return;
        }
        yield from $this->members($island);
    }

    public function lock(Island $island) : Generator {
        $islandLocked = $island->isLocked();

        $response = yield from $this->custom("Lock island", [
            new Label("label", "Lock or unlock your island"),
            new Toggle("lock", "Lock", $islandLocked)
        ]);

        if($response !== null) {
            $islandLocked = $response->getAll()["lock"];
            $island->setLocked($islandLocked);
            $this->getPlayer()->sendMessage("§aIsland locked: " . ($islandLocked ? "true" : "false"));
            return;
        }

        yield from $this->manager($island);
    }

    public function kick(Island $island) : Generator {
        $playersName = array_map(function(Player $player) {
            return $player->getName();
        }, $island->getPlayersInIsland());

        $response = yield from $this->custom("Kick player", [
            new Label("label", "Kick players who are on your island"),
            new Dropdown("name", "Name of player", $playersName)
        ]);

        if($response !== null) {
            $playerName = $response->getAll()["name"];
            $player = array_values($playersName)[$playerName];

            if(($player = Server::getInstance()->getPlayerExact($player)) !== null) {
                $provider = AzSkyBlock::getInstance()->getProvider();
                yield from $provider->awaitGet($player->getName(), function(?Island $island) use ($player) {
                    if(is_null($island)) {
                        $this->getPlayer()->sendMessage("§cPlayer not found");
                        return;
                    }
                    $player->teleport($island->getIslandSpawn());
                    $player->sendMessage("§cYou have been kicked from island " . $island->getIslandName());
                    $this->getPlayer()->sendMessage("§aPlayer " . $player->getName() . " has been kicked");
                });
                return;
            }
            $this->getPlayer()->sendMessage("§cPlayer is offline");
            return;
        }
        yield from $this->manager($island);
    }

    public function inviteVisit(Island $island) : Generator {
        $players = array_map(function(Player $player) {
            return $player->getName();
        }, Server::getInstance()->getOnlinePlayers());

        $response = yield from $this->custom("Visit member", [
            new Dropdown("name", "Name of player", $players)
        ]);

        if($response !== null) {
            $playerName = $response->getAll()["name"];
            $player = array_values($players)[$playerName];

            if(($player = Server::getInstance()->getPlayerExact($player)) !== null) {
                $this->getPlayer()->sendMessage("§cPlayer is online");
                return;
            }
            (new InvitedForm($player,
                "Player " . $this->getPlayer()->getName() . " invited you to visit island " . $island->getIslandName(),
                $island,
                function(bool $accept) use ($player, $island) {
                    if($accept) {
                        $player->teleport($island->getIslandSpawn());
                        $this->getPlayer()->sendMessage("§aPlayer accepted");
                    } else {
                        $this->getPlayer()->sendMessage("§cPlayer denied");
                    }
                }))->send();
        }
    }
}