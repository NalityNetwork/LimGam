<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Task\Game;


use LimGam\LimGam;
use LimGam\Task\LimTask;


/**
 * @author  RomnSD
 * @package LimGam\Task\Game
 */
class ExpiredLinksUpdater extends LimTask
{



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
        foreach ($this->game->getLinks() as $player => &$data)
        {
            if (--$data[1] === 0)
                $this->game->unlink($player);
        }
    }



}