<?php
declare(strict_types = 1);

namespace LimGam\Game\Event\Events\Arena;


use LimGam\Game\Arena;
use pocketmine\event\Event;


/**
 * @author RomnSD
 * @package LimGam\Game\Event\Events\Arena
 */
class GameOver extends Event
{



    /** @var Arena */
    protected $arena;



    /**
     * @param Arena $arena
     */
    public function __construct(Arena $arena)
    {
        $this->arena = $arena;
    }



    /**
     * @return Arena
     */
    public function getArena(): Arena
    {
        return $this->arena;
    }



}