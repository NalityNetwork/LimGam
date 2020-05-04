<?php /** @noinspection PhpUnused */
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
    public function __construct(int $priority = IGamEvent::PRIORITY_LOW)
    {
        parent::__construct($priority);
    }



    /**
     * @inheritDoc
     */
    public function Process(Event $event, $result)
    {
        /** @var EntityDamageEvent $event */
        if (!($player = $event->getEntity()) instanceof Player)
            return;

        /** @var Player $player */
        if (LimGam::GetGameManager()->GetSession($player->getName()))
            $event->setCancelled();
    }



    /**
     * @inheritDoc
     */
    public function GetName(): string
    {
        return "NoDamage";
    }



    /**
     * @inheritDoc
     */
    public function GetEvent(): string
    {
        return EntityDamageEvent::class;
    }



}