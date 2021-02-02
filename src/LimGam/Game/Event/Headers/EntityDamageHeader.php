<?php
declare(strict_types = 1);

namespace LimGam\Game\Event\Headers;


use LimGam\Game\Event\EventHeader;
use LimGam\LimGam;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Event;
use pocketmine\Player;


/**
 * @author  RomnSD
 * @package LimGam\Game\Event\Headers
 */
class EntityDamageHeader extends EventHeader
{



    /**
     * @inheritDoc
     */
    public function process(Event $event, $result)
    {
        /** @var EntityDamageEvent $event */
        if (!($event->getEntity() instanceof Player))
            return false;

        $session = LimGam::GetGameManager()->getSession($event->getEntity()->getName());

        if ($session === null)
            return false;

        if ($session->getArena()->getGame()->getName() !== $this->game)
            return false;

        return $session;
    }



    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "EntityDamageHeader";
    }



    /**
     * @inheritDoc
     */
    public function getEvent(): string
    {
        return EntityDamageEvent::class;
    }



    /**
     * @return bool
     */
    public function returnSession(): bool
    {
        return true;
    }



}