<?php /** @noinspection PhpUnused */
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
    protected $Session;

    /** @var string */
    protected $Reason;



    /**
     * @param InGame $session
     * @param string $reason
     */
    public function __construct(InGame $session, string $reason = "Unknown")
    {
        $this->Session = $session;
        $this->Reason  = $reason;
    }



    /**
     * @return InGame
     */
    public function GetSession(): InGame
    {
        return $this->Session;
    }



    /**
     * @return string
     */
    public function GetReason(): string
    {
        return $this->Reason;
    }



}