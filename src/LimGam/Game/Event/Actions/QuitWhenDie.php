<?php /** @noinspection PhpUnused */
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
    public function Process(Event $event, $result)
    {
        /** @var PlayerDeathEvent $event */
        if (!($result instanceof InGame))
            $result = LimGam::GetGameManager()->GetSession($event->getPlayer()->getName());

        if ($result)
            if ($result->GetArena()->GetStatus(Arena::STATUS_RUNNING))
                LimGam::GetGameManager()->RemoveSession($result->GetName());
    }



    /**
     * @inheritDoc
     */
    public function GetName(): string
    {
        return "QuitWhenDie";
    }



    /**
     * @inheritDoc
     */
    public function GetEvent(): string
    {
        return PlayerDeathEvent::class;
    }



}