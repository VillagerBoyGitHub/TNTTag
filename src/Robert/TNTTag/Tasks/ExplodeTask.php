<?php
// Credits to ZDarkGaming.
namespace Robert\TNTTag\Tasks;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use Robert\TNTTag\Main;

class ExplodeTask extends Task {
    private $base;
    private $seconds = 30;

    public function __construct(Main $base) {
        $this->base = $base;
    }

    public function onRun($tick) {
        foreach ($this->base->inGame as $pname) {
            $p = $this->base->getServer()->getPlayerExact($pname);
            if ($p !== null) {
                if ($this->seconds > 0) {
                    $p->sendPopup("§8§8[§cD§bW§8]§f: " . $this->base->tnter . "§eThe game will explode in §a{$this->seconds}!");
                } else {

                    foreach($this->base->inGame as $players) {
                        $players->sendMessage("§e" . $this->base->tnter . " has exploded!");
                        $this->base->leaveGame($players);
                    }
                }
            }
            
        }

        if ($this->seconds <= 0) {
            $this->base->getServer()->getScheduler()->cancelTask($this->getTaskId());
        }

        $this->seconds--;
    }
}