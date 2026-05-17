<?php
namespace Robert\TNTTag;

use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use Robert\TNTTag\Events\EventListener;
use pocketmine\Player;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use Robert\TNTTag\Commands\tnt;
use Robert\TNTTag\Tasks\PreGameCountdown;
use pocketmine\scheduler\Task;
use Robert\TNTTag\Tasks\ExplodeTask;
use pocketmine\level\sound\ExplodeSound;
use pocketmine\level\sound\FizzSound;
use pocketmine\math\Vector3;
use Robert\TNTTag\Tasks\PlayerWaiting;

class Main extends PluginBase {
    public $minimumPlayers = 2;
    
    public $inGame = [];
    public $hasStarted = false;
    public $tnter;
    private $conf;
    public function onEnable()
    {
        $this->getLogger()->info("TNTTag enabled.");
        Server::getInstance()->getPluginManager()->registerEvents(new EventListener($this), $this);
        Server::getInstance()->getCommandMap()->register("", new tnt($this));
        $this->conf = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
            "world" => "tnta",
            "x" => 0,
            "y" => 0,
            "z" => 0
        ]);
        Server::getInstance()->loadLevel($this->getWorld());
    }

    public function leaveGame(Player $p) {
        $name = $p->getName();
        unset($this->inGame[$name]);
        $p->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
        $p->getInventory()->clearAll();
        $p->removeAllEffects();
        $p->sendPopup(null);

        if(count($this->inGame) == 0 || count($this->inGame) == 1) {
            $this->hasStarted = false;
        }
        foreach ($this->inGame as $name) {
            $p = Server::getInstance()->getPlayerExact($name);
            $p->sendMessage(Server::getInstance()->getServerPrefix() . " " . $p->getName() . " §ahas left the game.");
        }

        $hub = Server::getInstance()->getPluginManager()->getPlugin("HubPatcher");
        $hub->tpToHub($p);
    }

    public function setSpawn($x, $y, $z, $worldName) {
        $this->getConfig()->setNested("x", $x);
        $this->getConfig()->save();

        $this->getConfig()->setNested("y", $y);
        $this->getConfig()->save();

        $this->getConfig()->setNested("z", $z);
        $this->getConfig()->save();

        $this->getConfig()->setNested("world", $worldName);
        $this->getConfig()->save();
    }
    public function joinGame($p) {
        $name = $p->getName();
        $this->inGame[$name] = $name;
        $world = Server::getInstance()->getLevelByName($this->conf->getNested("world"));
        if(count($this->inGame) >= $this->minimumPlayers) {
            $this->startGameCountdown();
        } elseif (count($this->inGame) < $this->minimumPlayers){
            Server::getInstance()->getScheduler()->scheduleRepeatingTask(new PlayerWaiting($this, $p), 20);
        }
        Server::getInstance()->loadLevel($this->conf->getNested("world"));
        if($world == null) {
            $this->getLogger()->error(Server::getInstance()->getServerPrefix() . " §cThe world that has been set in the TNTTag plugin doesn't exist.");
            return;
        }
        $p->teleport(new Position($this->conf->getNested("x"), $this->conf->getNested("y"), $this->conf->getNested("z"), Server::getInstance()->getLevelByName($this->getWorld())));
        
        foreach ($world->getPlayers() as $players) {
            $players->sendMessage(Server::getInstance()->getServerPrefix() . " " . $p->getName() . " §ahas joined the game.");
        }
        Server::getInstance()->getScheduler()->scheduleDelayedTask(new class($this, $p) extends Task {
            private $base, $p;
            public function __construct(Main $base, Player $p)
            {
                $this->base = $base;
                $this->p = $p;
            }
            public function onRun($currentTick)
            {
                $this->p->level->addSound(new FizzSound(new Vector3($this->p->getX(), $this->p->getY(), $this->p->getZ())));
            }
        }, 20);
        
    }
    public function getWorld() {
        return $this->conf->getNested("world");
    }
    public function startGameCountdown() {
        $this->hasStarted = true;
        Server::getInstance()->getScheduler()->scheduleRepeatingTask(new PreGameCountdown($this), 20);
        Server::getInstance()->getScheduler()->scheduleDelayedRepeatingTask(new class($this) extends Task {
    private $base;
    private $seconds = 30;

    public function __construct(Main $base) {
        $this->base = $base;
    }

    public function onRun($tick) {
        
        if ($this->seconds > 0) {
            foreach ($this->base->inGame as $pname) {
                $p = $this->base->getServer()->getPlayerExact($pname);
                $p->sendPopup(Server::getInstance()->getServerPrefix() . " §b" . $this->base->tnter . "§e will explode in §a{$this->seconds}!");
            }
        } else {

            $this->base->explodeTnter();
            $this->base->randomTnter();
            Server::getInstance()->getScheduler()->cancelTask($this->getTaskId());
        }

        $this->seconds--;
    }
}, 20 * 5, 20);
    }


    public function explodeTnter() {
        $tnter = $this->tnter;
        $tntplayer = Server::getInstance()->getPlayerExact($tnter);
        new ExplodeSound(new Vector3($tntplayer->getX(), $tntplayer->getY(), $tntplayer->getZ()));
        $this->leaveGame($tntplayer);
        foreach ($this->inGame as $pname) {
            $p = $this->getServer()->getPlayerExact($pname);
            $p->sendPopup(Server::getInstance()->getServerPrefix() . " §b" . $this->tnter . " has exploded!");
        }
    }

    public function randomTnter() {
        if(count($this->inGame) <= 1) {
            Server::getInstance()->getScheduler()->cancelAllTasks();
            foreach($this->inGame as $name) {
                $last = Server::getInstance()->getPlayerExact($name);
                $this->leaveGame($last);
            }

            $this->hasStarted = false;
            Server::getInstance()->broadcastMessage("§a". $name . " has won the game. GG!");
            $pl = $this->getServer()->getPluginManager()->getPlugin("StatsSystem");
            $pl->functions->addWin($name);
            return;
        }
        $random = array_rand($this->inGame);
        $randomized = Server::getInstance()->getPlayerExact($random);
        $this->setTnter($randomized);
    }

    public function setTnter(Player $p) {
        if($this->tnter != null){
            Server::getInstance()->getPlayerExact($this->tnter)->getInventory()->clearAll();
        }   

        $name = $p->getName();
        $this->tnter = $name;
        foreach ($this->inGame as $pname) {
            $player = $this->getServer()->getPlayerExact($pname);
            $player->sendMessage(Server::getInstance()->getServerPrefix() . " §b" . $this->tnter . " §eis the TNTer. Watch out!");
        }



        $p->getInventory()->setItem(0, new Item(ItemIds::TNT, 0, 64));
        $p->getInventory()->setItem(1, new Item(ItemIds::TNT, 0, 64));
        $p->getInventory()->setItem(2, new Item(ItemIds::TNT, 0, 64));
        $p->getInventory()->setItem(3, new Item(ItemIds::TNT, 0, 64));
        $p->getInventory()->setItem(4, new Item(ItemIds::TNT, 0, 64));
        $p->getInventory()->setItem(5, new Item(ItemIds::TNT, 0, 64));
        $p->getInventory()->setItem(6, new Item(ItemIds::TNT, 0, 64));
        $p->getInventory()->setItem(7, new Item(ItemIds::TNT, 0, 64));
        $p->getInventory()->setItem(8, new Item(ItemIds::TNT, 0, 64));
    }
    
}