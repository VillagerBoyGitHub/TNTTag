<?php
namespace Robert\TNTTag\Tasks;
use pocketmine\scheduler\Task;
use Robert\TNTTag\Main;
use pocketmine\Player;
class PlayerWaiting extends Task {
    private $p, $base;
    public function __construct(Main $base, Player $p)
    {
        $this->base = $base;
        $this->p = $p;
    }
    public function onRun($currentTick)
    {
        $name = $this->p->getName();
        if(!isset($this->base->inGame[$name])) {
            $this->base->getServer()->getScheduler()->cancelTask($this->getTaskId());
        }
        $remaining = $this->base->minimumPlayers - count($this->base->inGame);
        if($remaining === 1) {
            $this->p->sendTip("§cWaiting for " . $remaining . " more player.");
        } else {
            $this->p->sendTip("§cWaiting for " . $remaining . " more players.");
        }

        if ($remaining <= 0) {
            $this->base->getServer()->getScheduler()->cancelTask($this->getTaskId());
        }
    }

}