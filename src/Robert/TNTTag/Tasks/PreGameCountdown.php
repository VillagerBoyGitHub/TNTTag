<?php
// Credits to ZDarkGaming.
namespace Robert\TNTTag\Tasks;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use Robert\TNTTag\Main;

class PreGameCountdown extends Task {
    private $base;
    private $seconds = 5;

    public function __construct(Main $base) {
        $this->base = $base;
    }

    public function onRun($tick) {
        $inGame = $this->base->inGame;
        if ($this->seconds > 0) {

            
            foreach ($inGame as $pname) {
                $p = $this->base->getServer()->getPlayerExact($pname);
                $p->sendPopup("§8§8[§cD§bW§8]§f: §eThe game will start in §a{$this->seconds}.");
            }
        } else {
            $this->base->hasStarted = true;
            $random = array_rand($inGame);
            $randomized = Server::getInstance()->getPlayerExact($random);
            $this->base->setTnter($randomized);
            foreach ($inGame as $pname) {
                $p = $this->base->getServer()->getPlayerExact($pname);
                $p->sendPopup("§8§8[§cD§bW§8]§f: §aThe TNTTag game has started. Good luck!");
                $p->sendMessage($this->base->tnter . " is the TNTer. Watch out!");
            }
            
        }
    

        if ($this->seconds <= 0) {
            $this->base->getServer()->getScheduler()->cancelTask($this->getTaskId());
        }

        $this->seconds--;
    }
}