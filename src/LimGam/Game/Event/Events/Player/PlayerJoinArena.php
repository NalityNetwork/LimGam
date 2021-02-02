<?php
declare(strict_types = 1);

namespace LimGam\Game\Event\Events\Player;


use LimGam\Game\Session\InGame;
use pocketmine\event\Event;


/**
 * @author  RomnSD
 * @package LimGam\Game\Event\Player
 */
class PlayerJoinArena extends Event
{



    /** @var InGame */
    protected $session;



    /**
     * @param InGame $session
     */
    public function __construct(InGame $session)
    {
        $this->session = $session;
    }



    /**
     * @return InGame
     */
    public function getSession(): InGame
    {
        return $this->session;
    }



}