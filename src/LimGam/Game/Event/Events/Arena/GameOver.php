<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game\Event\Events\Arena;


use LimGam\Game\Arena;
use pocketmine\event\Event;


class GameOver extends Event
{



    /** @var Arena */
    protected $Arena;



    /**
     * @param Arena $arena
     */
    public function __construct(Arena $arena)
    {
        $this->Arena = $arena;
    }



    /**
     * @return Arena
     */
    public function GetArena(): Arena
    {
        return $this->Arena;
    }



}