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
    protected $ChunkID;



    /**
     * @param Game $game
     * @param int  $chunkID
     */
    public function __construct(Game $game, int $chunkID)
    {
        parent::__construct($game);

        $this->ChunkID = $chunkID;
    }



    /**
     * @inheritDoc
     */
    public function Start(int $period = 20, int $delay = 0): LimTask
    {
        LimGam::GetInstance()->getScheduler()->scheduleRepeatingTask($this, $period);
        return $this;
    }



    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick)
    {

        if (count($this->Game) < (5 * $this->ChunkID))
        {
            $this->Game->RemoveTask($this->getTaskId());
            return;
        }

        $arenas = array_values($this->Game->GetArenas());

        for ($i = $this->ChunkID + 4; $i >= $this->ChunkID; --$i)
        {
            if (isset($arenas[$i]))
                $arenas[$i]->Update($currentTick);
        }
    }



    public function __destruct()
    {
        if ($this->getHandler() && $this->Game)
            $this->Game->RemoveTask($this->getTaskId());
    }



}