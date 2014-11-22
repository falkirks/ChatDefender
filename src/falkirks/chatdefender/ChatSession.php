<?php
namespace falkirks\chatdefender;
use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

class ChatSession extends PluginTask{
    private $state;
    private $m;
    private $blockLength;
    /** @var  Player */
    private $player;
    public function bindToPlayer(Player $player){
        $this->player = $player;
        $this->blockLength = 1;
        $this->state = 0;
    }
    public function sendMessage($message){
        if($this->state == 2){
            $this->player->sendMessage("[ChatDefender] You are currently blocked.");
            return false;
        }
        elseif($this->similarityCheck($message) || $this->rateCheck()){
            if($this->state == 1){
                $this->state = 2;
                $this->player->sendMessage("[ChatDefender] You have been blocked for " . $this->blockLength * $this->getOwner()->getConfig()->get("baseblocklength") . "second(s)");
                $this->getOwner()->getServer()->getScheduler()->scheduleDelayedTask($this, 20*$this->getOwner()->getConfig()->get("baseblocklength")*$this->blockLength);
                $this->blockLength++;
                return false;
            }
            else{
                $this->state = 1;
                $this->player->sendMessage("[ChatDefender] Looks like you are posting spammy stuff. Continuing to do so will result in a block.");
                $this->m = array($message, time());
                return false;
            }
        }
        else{
            $this->m = array($message, time());
            return true;
        }
    }
    public function onRun($tick){
        $this->state = 0;
        $this->player->sendMessage("[ChatDefender] Your block has been lifted.");
    }
    public function similarityCheck($message){
        if($this->player->hasPermission("chatdefender.exempt.similar")) return false;
        return ((strlen($message) - similar_text($this->m[0],$message)) <= $this->getOwner()->getConfig()->get("similarity"));
    }
    public function rateCheck(){
        if($this->player->hasPermission("chatdefender.exempt.rate")) return false;
        return ((time() - $this->m[1]) <= $this->getOwner()->getConfig()->get("ratelimit"));
    }
    public function isBlocked(){
        return $this->state === 2;
    }
}