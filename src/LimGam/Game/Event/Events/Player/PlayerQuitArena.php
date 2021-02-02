<?php
declare(strict_types = 1);

namespace LimGam\Game\Event\Events\Player;


use LimGam\Game\Session\InGame;
use pocketmine\event\Event;


/**
 * @author  RomnSD
 * @package LimGam\Game\Event\Player
 */
class PlayerQuitArena extends Event
{



    /** @var InGame */
    protected $session;

    /** @var string */
    protected $reason;



    /**
     * @param InGame $session
     * @param string $reason
     */
    public function __construct(InGame $session, string $reason = "Unknown")
    {
        $this->session = $session;
        $this->reason  = $reason;
    }



    /**
     * @return InGame
     */
    public function getSession(): InGame
    {
        return $this->session;
    }



    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }



}