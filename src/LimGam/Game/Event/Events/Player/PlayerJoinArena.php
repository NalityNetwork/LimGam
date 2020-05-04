<?php /** @noinspection PhpUnused */
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
    protected $Session;



    /**
     * @param InGame $session
     */
    public function __construct(InGame $session)
    {
        $this->Session = $session;
    }



    /**
     * @return InGame
     */
    public function GetSession(): InGame
    {
        return $this->Session;
    }



}