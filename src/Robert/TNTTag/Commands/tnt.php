<?php

namespace Robert\TNTTag\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use Robert\TNTTag\Main;
use pocketmine\Server;
class tnt extends Command {
    protected $base;
    public function __construct(Main $base)
    {
        $this->base = $base;
        parent::__construct("tnt", "TNTTag commands", Server::getInstance()->getServerPrefix() . " §eUsage: /tnt join", ["ttag", "tag"]);
    }

    public function execute(CommandSender $p, $commandLabel, array $args)
    {
        switch($this->getName()) {
            case "tnt":
                if(!isset($args[0])) {
                    $this->getUsage();
                    return;
                }
                switch($args[0]) {
                    case "join":
                        $name = $p->getName();
                        if(isset($this->base->inGame[$name])) {
                            $p->sendMessage(Server::getInstance()->getServerPrefix() . " §eYou are already in a game.");
                            return;
                        } elseif($this->base->hasStarted == true) {
                            $p->sendMessage(Server::getInstance()->getServerPrefix() . " §cThe game has already started.");
                            return;
                        }
                        $this->base->joinGame($p);
                        break;
                }
                break;
        }
    }
}