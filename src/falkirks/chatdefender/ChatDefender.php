<?php
namespace falkirks\chatdefender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class ChatDefender extends PluginBase implements Listener{
    /** @var  ChatSession[] */
    public $sessions;
    public function onEnable(){
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->s = [];
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder(). "config.yml", Config::YAML, array(
            "similarity" => 1,
            "ratelimit" => 1,
            "blockme" => true,
            "baseblocklength" => 60,
            ));
    }
    public function onChat(PlayerChatEvent $event){
        if(!isset($this->sessions[$event->getPlayer()->getName()])){
            $this->sessions[$event->getPlayer()->getName()] = new ChatSession($this);
            $this->sessions[$event->getPlayer()->getName()]->bindToPlayer($event->getPlayer());
        }
        if(!$this->sessions[$event->getPlayer()->getName()]->sendMessage($event->getMessage())){
            $event->setCancelled();
        }
    }
    public function onQuit(PlayerQuitEvent $event){
        if(isset($this->sessions[$event->getPlayer()->getName()])) unset($this->sessions[$event->getPlayer()->getName()]);
    }
    public function onCommandPreProcess(PlayerCommandPreprocessEvent $event){
        if($this->getConfig()->get("blockme") && isset($this->sessions[$event->getPlayer()->getName()]) && $this->sessions[$event->getPlayer()->getName()]->isBlocked()){
            $args = explode(" ", $event->getMessage());
            if($args[0] == "/me"){
                $event->setCancelled();
            }
        }
    }
}
