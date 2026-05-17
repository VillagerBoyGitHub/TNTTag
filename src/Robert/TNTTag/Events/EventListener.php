<?php

namespace Robert\TNTTag\Events;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use Robert\TNTTag\Main;
use pocketmine\Server;
class EventListener implements Listener {
    protected $base;
    public function __construct(Main $base)
    {
        $this->base = $base;
    }

    public function onCommandPreprocess(PlayerCommandPreprocessEvent $e) {
        $p = $e->getPlayer();
        $name = $p->getName();
        if(isset($this->base->inGame[$name]) && $p->getLevel()->getFolderName() == $this->base->getWorld()) {
            
            $hub = ["/hub", "/lobby", "/leave"];
            $msg = $e->getMessage();
            $msg = explode(" ", $msg);
            if(in_array($msg[0], $hub)) {
                
                    if($this->base->hasStarted) {
                        $p->sendMessage(Server::getInstance()->getServerPrefix() . " §cYou can't leave while you're in a game.");
                        $e->setCancelled();
                        return;
                    }
                    $e->setCancelled();
                    
                
                $this->base->leaveGame($p);

                
                foreach($this->base->inGame as $name) {
                    $p = Server::getInstance()->getPlayerExact($name);
                    $p->sendMessage(Server::getInstance()->getServerPrefix() . "§e" . $p->getName() . " §chas left the game.");
                }
            }
        }
    }

    public function onBlockBreak(BlockBreakEvent $e) {
        $p = $e->getPlayer();
        $name = $p->getName();
        if(isset($this->base->inGame[$name]) && $p->getLevel()->getName() == $this->base->getWorld()) {
            $e->setCancelled();
        }
    }

    public function onBlockPlace(BlockPlaceEvent $e) {
        $p = $e->getPlayer();
        $name = $p->getName();
        if(isset($this->base->inGame[$name]) == true && $p->getLevel()->getName() == $this->base->getWorld()) {
            $e->setCancelled();
        }
    }

    /*public function onInteract(PlayerInteractEvent $e) {
        $p = $e->getPlayer();
        $name = $p->getName();
        if(isset($this->base->inGame[$name]) && $p->getLevel()->getName() == $this->base->getWorld()) {
            $e->setCancelled();
        }
    }*/

    public function onDamage(EntityDamageEvent $e) {
        $c = $e->getCause();
        $p = $e->getEntity();

        if($p instanceof Player) {
            if($p->getLevel()->getName() == $this->base->getWorld()) {            
                if($c == $e::CAUSE_ENTITY_ATTACK || $c == $e::CAUSE_FALL || $c == $e::CAUSE_CONTACT) {
                    $e->setCancelled();
                }
            }
        }

        if($e instanceof EntityDamageByEntityEvent) {
            $hitman = $e->getDamager(); // LOL I'm so not creative with names 😂😂
            $victim = $e->getEntity();

            if($hitman instanceof Player) {
                if($this->base->hasStarted && $hitman->getName() == $this->base->tnter) {

                    $e->setCancelled();

                    $this->base->setTnter($victim);
                    $hitman->getInventory()->clearAll();

                }
            }
        }
    }

    public function onDeath(PlayerDeathEvent $e) {
        $p = $e->getPlayer();
        $name = $p->getName();
        if(isset($this->base->inGame[$name])) {
            $this->base->leaveGame($p);
        }
    }

    public function onLeave(PlayerQuitEvent $e) {
        $p = $e->getPlayer();
        if($p == null) return;
        $name = $p->getName();
        if(isset($this->base->inGame[$name])) {
            $this->base->leaveGame($p);
        }

    }
}