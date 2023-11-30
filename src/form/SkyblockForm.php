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
use faz\common\form\FastForm;
use Generator;
use phuongaz\azskyblock\AzSkyBlock;
use phuongaz\azskyblock\island\components\Warp;
use phuongaz\azskyblock\island\Island;
use phuongaz\azskyblock\utils\IslandSettings;
use phuongaz\azskyblock\utils\LanguageUtils;
use phuongaz\azskyblock\world\custom\CustomIsland;
use phuongaz\azskyblock\world\custom\IslandPool;
use phuongaz\azskyblock\world\WorldUtils;
use pocketmine\math\Vector3;
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
        yield from $provider->awaitGet($this->getPlayer()->getName(), function(?Island $island) {
            Await::f2c(function() use ($island) {
                if (is_null($island)) {
                    yield from $this->chooseIsland();
                    return;
                }
                $this->handleMenuOptions($island);
            });
        });
    }

    private function handleMenuOptions(Island $island): void {
        Await::f2c(function() use ($island) {
            $menuOptions = [
                new MenuOption(LanguageUtils::translate("menu.main.home")),
                new MenuOption(LanguageUtils::translate("menu.main.teleport")),
                new MenuOption(LanguageUtils::translate("menu.main.manager")),
                new MenuOption(LanguageUtils::translate("menu.main.warp"))
            ];

            $menuChoose = yield from $this->menu(
                LanguageUtils::translate("menu.main.title"),
                LanguageUtils::translate("menu.main.conent", ["island" => $island->getIslandName()]),
                $menuOptions
            );

            if($menuChoose === null) {
                return;
            }

            switch ($menuChoose) {
                case 0:
                    $this->getPlayer()->teleport($island->getIslandSpawnPosition());
                    break;
                case 1:
                    yield from $this->teleport($island);
                    break;
                case 2:
                    yield from $this->manager($island);
                    break;
                case 3:
                    yield from $this->warps($island);
                    break;
            }
        });
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
            $player = $this->getPlayer();
            if($name === "") {
                FastForm::simpleNotice($player, "§cName of warp cannot be empty", function () use ($island) {
                    Await::g2c($this->createWarp($island));
                });
                return;
            }

            if(!$player->getWorld()->getFolderName() != WorldUtils::getSkyBlockWorld()->getFolderName()) {
                $this->getPlayer()->sendMessage("§cYou must be in skyblock world");
                return;
            }

            $isAdded = $island->addWarp($name, $this->getPlayer()->getPosition()->asVector3(), true);

            $message = $isAdded ? "§aWarp " . $name . " has been created" : "§cWarp " . $name . " already exists";

            FastForm::simpleNotice($this->getPlayer(), $message, function () use ($island) {
                Await::g2c($this->warps($island));
            });
            return;
        }
        yield from $this->warps($island);
    }

    public function removeWarp(Island $island) : Generator {
        $warps = $island->getIslandWarps();

        $warpOptions = array_map(function(Warp $warp) {
            return new MenuOption($warp->getWarpName());
        }, $warps);

        if(count($warpOptions) === 0) {
            $this->getPlayer()->sendMessage("§cYour island has no warp");
            return;
        }

        $warpChoose = yield from $this->menu(
            "Teleport warp",
            $island->getIslandName(),
            $warpOptions
        );

        if(!is_null($warpChoose)) {
            FastForm::question($this->getPlayer(), "Confirm", "Remove warp " . $warps[$warpChoose]->getWarpName() . "?", "Yes", "No", function(bool $accept) use ($island, $warps, $warpChoose) {
                if($accept) {
                    $island->removeWarp($warps[$warpChoose]->getWarpName(), true);
                    FastForm::simpleNotice($this->getPlayer(), "Warp " . $warps[$warpChoose]->getWarpName() . " has been removed", function () use ($island) {
                        Await::g2c($this->warps($island));
                    });
                    return;
                }
                Await::g2c($this->warps($island));
            });
            return;
        }
        yield from $this->warps($island);
    }

    public function teleportWarp(Island $island) : Generator {
        $warps = $island->getIslandWarps();

        $warpOptions = array_map(function(Warp $warp) {
            return new MenuOption($warp->getWarpName());
        }, $warps);

        if(count($warpOptions) === 0) {
            $this->getPlayer()->sendMessage("§cYour island has no warp");
            return;
        }

        $warpChoose = yield from $this->menu(
            "Teleport warp",
            $island->getIslandName(),
            $warpOptions
        );

        if(!is_null($warpChoose)) {
            $warp = array_values($warps)[$warpChoose];
            $this->getPlayer()->teleport($warp->getWarpPosition());
            $this->getPlayer()->sendMessage("§aTeleported to " . $warp->getWarpName());
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
            yield from $provider->awaitGet($name, function(?Island $islandTarget) use ($island) {
                if(is_null($islandTarget)) {
                    $this->getPlayer()->sendMessage("§cPlayer not found");
                    return;
                }
                if($islandTarget->isLocked()) {
                    FastForm::simpleNotice($this->getPlayer(), "Island ". $islandTarget->getIslandName()." locked", function () use ($island) {
                        Await::g2c($this->teleport($island));
                    });
                    return;
                }
                $spawn = $islandTarget->getIslandSpawn();
                $this->getPlayer()->teleport($spawn);
                $islandInfo = "Teleported to island " . $islandTarget->getIslandName() . "\n";
                $islandInfo .= "Island: " . $islandTarget->getIslandName() . "\n";
                $islandInfo .= "Owner: " . $islandTarget->getPlayer() . "\n";
                $islandInfo .= "Members: " . implode(", ", $islandTarget->getMembers()) . "\n";
                $islandInfo .= "Locked: " . "No" . "\n"; //Because it's not locked
                $islandInfo .= "Warps: " . implode(", ", $islandTarget->getIslandWarps()) . "\n";
                $islandInfo .= "Created: " . $islandTarget->getDateCreated() . "\n";
                $islandInfo .= "Level: " . $islandTarget->getIslandLevel()->getLevelInt() . "\n";

                FastForm::simpleNotice($this->getPlayer(), $islandInfo);
            });
            return;
        }
        yield from $this->main();
    }

    public function chooseIsland(): Generator {
        /** @var CustomIsland[] $islands */
        $islands = array_values(IslandPool::getAll());
        $menuOptions = [];

        foreach($islands as $island) {
            $menuOptions[] = new MenuOption($island->getName());
        }

        $menuChoose = yield from $this->menu(
            "Choose island",
            "Choose island",
            $menuOptions
        );

        if($menuChoose === null) {
            yield from $this->main();
            return;
        }

        /** @var CustomIsland $island*/
        $island = array_values(IslandPool::getAll())[$menuChoose];

        $confirm = yield from $this->modal(
            "Confirm",
            "Create island " . $island->getName() . "?"
        );

        if($confirm) {
            $this->getPlayer()->sendMessage("§aCreating island " . $island->getName());
            $island->generate(function(Position|Vector3 $spawn, bool $hasGiven){
                $this->getPlayer()->sendMessage("Island created");
                $player = $this->getPlayer()->getName();
                $island = Island::new($player, $player . "'s island", $spawn);
                $provider = AzSkyBlock::getInstance()->getProvider();
                Await::g2c($provider->awaitCreate($player, $island, function(?Island $island) use ($hasGiven) {
                    $island->teleportToIsland($this->getPlayer());
                    if(!$hasGiven) {
                        $this->getPlayer()->getInventory()->addItem(...IslandSettings::getStartItems());
                    }
                }));
            });
            return;
        }
        yield from $this->chooseIsland();
    }

    public function manager(Island $island) : Generator {
        $menuOptions = [
            new MenuOption("Information"),
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
            yield from $this->information($island);
        }

        if($menuChoose === 1) {
            yield from $this->inviteVisit($island);
        }

        if($menuChoose === 2) {
            yield from $this->kick($island);
        }

        if($menuChoose === 3) {
            yield from $this->lock($island);
        }

        if($menuChoose === 4) {
            yield from $this->members($island);
        }
    }

    public function information(Island $island) : Generator {

        $warpsName = array_map(function(Warp $warp) {
            return $warp->getWarpName();
        }, $island->getIslandWarps());
        $name = $island->getIslandName();
        $isLocked = $island->isLocked();

        $elements = [
            new Input("name", "Name of island", $name, $name),
            new Label("owner", "Owner: " . $island->getPlayer()),
            new Label("members", "Members: " . implode(", ", $island->getMembers())),
            new Toggle("lock", "Lock", $isLocked),
            new Label("warps", "Warps: " . implode(", ", $warpsName)),
            new Label("created", "Created: " . $island->getDateCreated()),
        ];

        /** @var CustomFormResponse|null $response*/
        $response = yield from $this->custom("Information", $elements);
        if($response !== null) {
            $data = $response->getAll();
            $name = $data["name"];
            $locked = $data["lock"];
            if($name === "") {
                FastForm::simpleNotice($this->getPlayer(), "§cName of island cannot be empty", function () use ($island) {
                    Await::g2c($this->information($island));
                });
                return;
            }

            if($name !== $island->getIslandName() or $isLocked !== $locked) {
                $island->setIslandName($name);
                $island->setLocked($locked);
                $island->save();
                FastForm::simpleNotice($this->getPlayer(), "Island has been updated", function () use ($island) {
                    Await::g2c($this->information($island));
                });
            }
            return;
        }
        yield from $this->manager($island);
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
        $playersOnline = Server::getInstance()->getOnlinePlayers();

        $membersName = array_map(function(Player $player) {
            return $player->getName();
        }, $playersOnline);

        unset($membersName[$this->getPlayer()->getName()]);

        $response = yield from $this->custom("Members", [
            new Label("label", "add member of your island"),
            new Dropdown("name", "Players online", $membersName)
        ]);

        if($response !== null) {
            $playerName = $response->getAll()["name"];
            $player = array_values($membersName)[$playerName];

            if(($player = Server::getInstance()->getPlayerExact($player)) !== null) {
                $this->getPlayer()->sendMessage("§cPlayer is online");
                return;
            }
            FastForm::question($player, "Invite", "Player " . $this->getPlayer()->getName() . " invited you to join island " . $island->getIslandName(), "Accept", "Deny", function(bool $accept) use ($player, $island) {
                if($accept) {
                    $island->addMember($player->getName());
                    $this->getPlayer()->sendMessage("§aPlayer " . $player->getName() . " has been added");
                    $player->sendMessage("You have been added to island " . $island->getIslandName() . " by " . $this->getPlayer()->getName());
                } else {
                    $this->getPlayer()->sendMessage("§cPlayer denied");
                }
            });
        }
        yield from $this->members($island);
    }

    public function removeMembers(Island $island) : Generator {
        $members = $island->getMembers();
        $membersName = array_map(function(string $member) {
            return $member;
        }, $members);

        if(isset($membersName[$this->getPlayer()->getName()])) {
            unset($membersName[$this->getPlayer()->getName()]);
        }

        $response = yield from $this->custom("Members", [
            new Label("label", "Members of your island"),
            new Dropdown("name", "Name of player", $membersName)
        ]);

        if($response !== null) {
            $playerName = $response->getAll()["name"];
            $player = array_values($membersName)[$playerName];

            if($playerName == "") {
                yield from $this->members($island);
                return;
            }

            if($island->hasMember($player)) {
                $island->removeMember($player);
                FastForm::simpleNotice($this->getPlayer(), "Player " . $player . " has been removed", function () use ($island) {
                    Await::g2c($this->members($island));
                });
                return;
            }
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
            FastForm::simpleNotice($this->getPlayer(), "Island has been " . ($islandLocked ? "locked" : "unlocked"), function () use ($island) {
                Await::g2c($this->manager($island));
            });
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
                    FastForm::question($this->getPlayer(), "Confirm", "Kick player " . $player->getName() . "?", "Yes", "No", function(bool $accept) use ($player, $island) {
                        if($accept) {
                            $island->removeMember($player->getName());
                            FastForm::simpleNotice($this->getPlayer(), "Player " . $player->getName() . " has been kicked", function () use ($island) {
                                Await::g2c($this->manager($island));
                            });
                            return;
                        }
                        Await::g2c($this->manager($island));
                    });
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
            FastForm::question($player, "Invite", "Player " . $this->getPlayer()->getName() . " invited you to teleport to island " . $island->getIslandName(), "Accept", "Deny", function(bool $accept) use ($player, $island) {
                if($accept) {
                    $player->teleport($island->getIslandSpawnPosition());
                    $this->getPlayer()->sendMessage("§aPlayer accepted");
                } else {
                    $this->getPlayer()->sendMessage("§cPlayer denied");
                }
            });
        }
    }
}