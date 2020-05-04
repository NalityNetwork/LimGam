<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game\Event\Actions;


use LimGam\Game\Event\EventAction;
use LimGam\Game\Event\IGamEvent;
use LimGam\Game\Session\InGame;
use LimGam\LimGam;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Event;
use pocketmine\Player;


/**
 * @author  RomnSD
 * @package LimGam\Game\Event\Actions
 */
class NoExternalDamage extends EventAction
{



    public function __construct()
    {
        parent::__construct(IGamEvent::PRIORITY_LOW);
    }



    /**
     * @inheritDoc
     */
    public function Process(Event $event, $result)
    {
        if (!($result instanceof InGame))
            return;

        if (!($event instanceof EntityDamageByEntityEvent) && !($event instanceof EntityDamageByChildEntityEvent))
            return;

        $attacker = ($event instanceof EntityDamageByEntityEvent) ? $event->getDamager() : $event->getChild()->getOwningEntity();

        if (!($attacker instanceof Player))
            return;

        if (!($attacker_session = LimGam::GetGameManager()->GetSession($attacker->getName())))
        {
            if (!$attacker->hasPermission("allow.external.damage"))
                $event->setCancelled();

            return;
        }

        if ($attacker_session->GetArena() !== $result->GetArena())
        {
            $event->setCancelled();
            return;
        }

        if ($attacker_session->GetStatus() !== $result->GetStatus())
        {
            $event->setCancelled();
            return;
        }
    }



    /**
     * @inheritDoc
     */
    public function GetName(): string
    {
        return "NoExternalDamage";
    }



    /**
     * @inheritDoc
     */
    public function GetEvent(): string
    {
        return EntityDamageEvent::class;
    }



}