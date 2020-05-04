<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game\Event\Actions;


use LimGam\Game\Arena;
use LimGam\Game\Event\EventAction;
use LimGam\Game\Event\IGamEvent;
use LimGam\Game\Session\InGame;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Event;


/**
 * @author  RomnSD
 * @package LimGam\Game\Event\Actions
 */
class DamageOnlyWhenRunning extends EventAction
{



    /**
     * @param int $priority
     */
    public function __construct($priority = IGamEvent::PRIORITY_LOW)
    {
        parent::__construct($priority);
    }



    /**
     * @inheritDoc
     */
    public function Process(Event $event, $result)
    {
        if ($event->isCancelled() || !($result instanceof InGame))
            return;

        if ($result->GetArena()->GetStatus() === Arena::STATUS_RUNNING)
            $event->setCancelled();
    }



    /**
     * @inheritDoc
     */
    public function GetName(): string
    {
        return "DamageOnlyWhenRunning";
    }



    /**
     * @inheritDoc
     */
    public function GetEvent(): string
    {
        return EntityDamageEvent::class;
    }



}