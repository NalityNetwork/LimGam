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
    public function Start(int $period = 5 * 20, int $delay = 0): LimTask
    {
        LimGam::GetInstance()->getScheduler()->scheduleRepeatingTask($this, $period);
        return $this;
    }



    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick)
    {
        $time = time();

        foreach ($this->Game->GetLinks() as $player => $data)
            if (($time - $data[1]) >= 30)
                $this->Game->Unlink($player);
    }



}