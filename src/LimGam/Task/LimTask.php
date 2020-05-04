<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Task;


use LimGam\Game\Game;
use pocketmine\scheduler\Task;


/**
 * @author  RomnSD
 * @package LimGam\Task
 */
abstract class LimTask extends Task
{



    /** @var Game */
    protected $Game;



    /**
     * @param Game $game
     */
    public function __construct(Game $game)
    {
        $this->Game = $game;
    }



    /**
     * @return Game
     */
    public function GetGame(): Game
    {
        return $this->Game;
    }



    /**
     * @param int $period
     * @param int $delay
     * @return LimTask
     */
    public abstract function Start(int $period, int $delay = 0): self;



}