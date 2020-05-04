<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game\Event\Actions;


use LimGam\Game\Arena;
use LimGam\Game\Event\EventAction;
use LimGam\Game\Event\Events\Entity\FakeDeathEvent;
use LimGam\Game\Event\IGamEvent;
use LimGam\Game\Session\InGame;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Event;


/**
 * @author  RomnSD
 * @package LimGam\Game\Event\Actions
 */
class CleanDeath extends EventAction
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
    public function Process(Event $event, $result)
    {
        if ($event->isCancelled() || !($result instanceof InGame))
            return;

        /** @var EntityDamageEvent $event */
        if ($event->getFinalDamage() >= $event->getEntity()->getHealth())
        {
            if ($result->GetArena()->GetStatus(Arena::STATUS_RUNNING))
                (new FakeDeathEvent($event->getEntity(), ($event->getEntity() instanceof Living) ? $event->getEntity()->getDrops() : []))->call();

            $event->setCancelled();
        }
    }



    /**
     * @inheritDoc
     */
    public function GetName(): string
    {
        return "CleanDeath";
    }



    /**
     * @inheritDoc
     */
    public function GetEvent(): string
    {
        return EntityDamageEvent::class;
    }



}