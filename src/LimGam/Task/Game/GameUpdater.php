<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Task\Game;


use LimGam\LimGam;
use LimGam\Game\Game;
use LimGam\Task\LimTask;


/**
 * @author  GameUpdater
 * @package LimGam\Task\Game
 */
class GameUpdater extends LimTask
{



    /** @var int */
    protected $chunkID;



    /**
     * @param Game $game
     * @param int  $chunkID
     */
    public function __construct(Game $game, int $chunkID)
    {
        parent::__construct($game);

        $this->chunkID = $chunkID;
    }



    /**
     * @inheritDoc
     */
    public function start(int $period = 20, int $delay = 0): LimTask
    {
        LimGam::GetInstance()->getScheduler()->scheduleRepeatingTask($this, $period);
        return $this;
    }



    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick)
    {

        if (count($this->game) < (5 * $this->chunkID))
        {
            $this->game->removeTask($this->getTaskId());
            return;
        }

        $arenas = array_values($this->game->getArenas());

        for ($i = $this->chunkID + 4; $i >= $this->chunkID; --$i)
        {
            if (isset($arenas[$i]))
                $arenas[$i]->update($currentTick);
        }
    }



    public function __destruct()
    {
        if ($this->getHandler() && $this->game)
            $this->game->removeTask($this->getTaskId());
    }



}