<?php /** @noinspection PhpUnused */
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
    public function Process(Event $event, $result)
    {
        /** @var EntityDamageEvent $event */
        if (!($event->getEntity() instanceof Player))
            return false;

        $session = LimGam::GetGameManager()->GetSession($event->getEntity()->getName());

        if ($session === null)
            return false;

        if ($session->GetArena()->GetGame()->GetName() !== $this->Game)
            return false;

        return $session;
    }



    /**
     * @inheritDoc
     */
    public function GetName(): string
    {
        return "EntityDamageHeader";
    }



    /**
     * @inheritDoc
     */
    public function GetEvent(): string
    {
        return EntityDamageEvent::class;
    }



    /**
     * @return bool
     */
    public function ReturnSession(): bool
    {
        return true;
    }



}