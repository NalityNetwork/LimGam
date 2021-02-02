<?php
declare(strict_types = 1);

namespace LimGam\Game\Event\Actions;


use LimGam\Game\Event\EventAction;
use LimGam\Game\Event\IGamEvent;
use LimGam\LimGam;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Event;
use pocketmine\Player;


/**
 * @author  RomnSD
 * @package LimGam\Game\Event\Actions
 */
class NoDamage extends EventAction
{



    /**
     * @param int $priority
     */
    public function __construct(int $priority = IGamEvent::PRIORITY_HIGH)
    {
        parent::__construct($priority);
    }



    /**
     * @inheritDoc
     */
    public function process(Event $event, $result)
    {
        /** @var EntityDamageEvent $event */
        if (!($event->getEntity()) instanceof Player)
            return;

        if (LimGam::getGameManager()->getSession($event->getEntity()->getName()))
            $event->setCancelled();
    }



    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "NoDamage";
    }



    /**
     * @inheritDoc
     */
    public function getEvent(): string
    {
        return EntityDamageEvent::class;
    }



}