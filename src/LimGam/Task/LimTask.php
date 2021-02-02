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
    protected $game;



    /**
     * @param Game $game
     */
    public function __construct(Game $game)
    {
        $this->game = $game;
    }



    /**
     * @return Game
     */
    public function getGame(): Game
    {
        return $this->game;
    }



    /**
     * @param int $period
     * @param int $delay
     * @return LimTask
     */
    public abstract function start(int $period, int $delay = 0): self;



}