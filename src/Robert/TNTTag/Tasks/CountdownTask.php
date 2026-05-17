<?php

namespace Robert\TNTTag\Tasks;

use pocketmine\scheduler\Task;

class CountdownTask extends Task {
    private $plugin;
    private $seconds = 5;

    public function __construct($plugin) {
        $this->plugin = $plugin;
    }

    public function onRun($tick) {
        foreach ($this->plugin->playersInGame as $pname) {
            $p = $this->plugin->getServer()->getPlayerExact($pname);
            if ($p !== null) {
                if ($this->seconds > 0) {
                    $p->sendPopup("§8§8[§cD§bW§8]§f: §eGame starts in §a{$this->seconds}");
                } else {
                    $p->sendPopup("§8§8[§cD§bW§8]§f: §aGame Started!");
                    $this->plugin->frozenPlayers[$pname] = false;
                }
            }
        }

        if ($this->seconds <= 0) {
            $this->plugin->gameStarting = false;
            $this->plugin->getServer()->getScheduler()->cancelTask($this->getTaskId());
        }

        $this->seconds--;
    }
}