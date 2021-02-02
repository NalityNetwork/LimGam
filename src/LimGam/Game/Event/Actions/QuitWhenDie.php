<?php
declare(strict_types = 1);

namespace LimGam\Game\Event\Actions;


use LimGam\Game\Arena;
use LimGam\Game\Event\EventAction;
use LimGam\Game\Session\InGame;
use LimGam\LimGam;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerDeathEvent;


/**
 * @author  RomnSD
 * @package LimGam\Game\Event\Actions
 */
class QuitWhenDie extends EventAction
{



    /**
     * @inheritDoc
     */
    public function process(Event $event, $result)
    {
        /** @var PlayerDeathEvent $event */
        if (!($result instanceof InGame))
            $result = LimGam::GetGameManager()->getSession($event->getPlayer()->getName());

        if ($result)
            if ($result->getArena()->getStatus(Arena::STATUS_RUNNING))
                LimGam::GetGameManager()->removeSession($result->getName());
    }



    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "QuitWhenDie";
    }



    /**
     * @inheritDoc
     */
    public function getEvent(): string
    {
        return PlayerDeathEvent::class;
    }



}