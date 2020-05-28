<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game\Event\Actions;


use LimGam\Game\Event\EventAction;
use LimGam\Game\Event\IGamEvent;
use LimGam\Game\Session\InGame;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Event;
use pocketmine\Player;


/**
 * @author  RomnSD
 * @package LimGam\Game\Event\Actions
 */
class NoTeamDamage extends EventAction
{



    /**
     * @param int $priority
     */
    public function __construct(int $priority = IGamEvent::PRIORITY_LOW)
    {
        parent::__construct($priority);
    }



    /**
     * @inheritDoc
     */
    public function Process(Event $event, $result)
    {
        if ($event->isCancelled() || !($result instanceof InGame) || !($event instanceof EntityDamageByEntityEvent) && !($event instanceof EntityDamageByChildEntityEvent))
            return;

        $attacker = ($event instanceof EntityDamageByEntityEvent) ? $event->getDamager() : $event->getChild()->getOwningEntity();

        if (($attacker instanceof Player) && $result->GetTeam()->IsMember($attacker->getName()))
            $event->setCancelled();
    }



    /**
     * @inheritDoc
     */
    public function GetName(): string
    {
        return "NoTeamDamage";
    }



    /**
     * @inheritDoc
     */
    public function GetEvent(): string
    {
        return EntityDamageEvent::class;
    }



}