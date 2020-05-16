<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game;


use Countable;
use Exception;
use InvalidArgumentException;
use LimGam\LimGam;
use LimGam\Game\Event\IGamEvent;
use LimGam\Task\Game\ExpiredLinksUpdater;
use LimGam\Task\Game\GameUpdater;
use Throwable;


/**
 * @author  RomnSD
 * @package LimGam\Game
 */
class Game implements Countable
{



    /** @var string */
    protected $Name;

    /** @var Arena[] */
    protected $Arenas;

    /** @var string[] */
    protected $Links;

    /** @var IGamEvent[] */
    protected $Events;

    /** @var GameUpdater[] */
    protected $Tasks;

    /** @var ExpiredLinksUpdater */
    protected $LinksUpdater;



    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->Name         = $name;
        $this->Arenas       = [];
        $this->Links        = [];
        $this->Tasks        = [0 => (new GameUpdater($this, 0))->Start()];
        $this->LinksUpdater = (new ExpiredLinksUpdater($this))->Start();
    }



    /**
     * @return string
     */
    public function GetName(): string
    {
        return $this->Name;
    }



    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->Arenas);
    }



    /**
     * @return array
     */
    public function GetArenas(): array
    {
        return $this->Arenas;
    }



    /**
     * @param string $player
     * @param string $arenaID
     */
    public function Link(string $player, string $arenaID): void
    {
        $this->Links[$player] = [$arenaID, time()];
    }



    /**
     * @param string $player
     */
    public function Unlink(string $player): void
    {
        unset($this->Links[$player]);
    }



    /**
     * @return array|string[]
     */
    public function GetLinks(): array
    {
        return $this->Links;
    }



    /**
     * @return ExpiredLinksUpdater|null
     */
    public function GetLinksUpdater(): ?ExpiredLinksUpdater
    {
        return $this->LinksUpdater;
    }



    /**
     * @param Arena $arena
     * @throws Exception
     */
    public function AddArena(Arena $arena): void
    {
        if (isset($this->Arenas[$arena->GetID()]))
            throw new Exception("Cannot add twice an arena.");

        $this->Arenas[$arena->GetID()] = $arena;

        $total = $this->count();
        $div   = ($total / 5);

        if (($total % 5) === 0)
            $this->Tasks[$div] = (new GameUpdater($this, $div))->Start();
    }



    /**
     * @param string $arenaID
     */
    public function RemoveArena(string $arenaID): void
    {
        if (!isset($this->Arenas[$arenaID]))
            return;

        try
        {
            $this->Arenas[$arenaID]->Close();
        }
        catch (Throwable $e)
        {
            LimGam::GetInstance()->getLogger()->logException($e);
        }
        finally
        {
            unset($this->Arenas[$arenaID]);
        }

    }



    /**
     * @param string $id
     * @return Arena|null
     */
    public function GetArena(string $id): ?Arena
    {
        return ($this->Arenas[$id] ?? null);
    }



    /**
     * @param string|null $player
     * @return Arena|null
     */
    public function GetFreeArena(string $player = null): ?Arena
    {
        if ($player && isset($this->Links[$player]))
            return $this->GetArena($this->Links[$player][0]);

        foreach ($this->Arenas as $arena)
        {
            if ($arena->GetFreeSlots() > 0 && $arena->IsJoinable())
                return $arena;
        }

        return null;
    }



    /**
     * @param int $taskID
     */
    public function RemoveTask(int $taskID): void
    {
        if (count($this->Tasks) < 2)
            return;

        /** @var GameUpdater $task */
        foreach ($this->Tasks as $i => $task)
        {
            if ($task->getTaskId() !== $taskID)
                continue;

            LimGam::GetInstance()->getScheduler()->cancelTask($taskID);
            unset($this->Tasks[$taskID]);
        }
    }



    /**
     * @param int $taskID
     */
    public function RemoveTaskByChunkID(int $taskID): void
    {
        if (count($this->Tasks) < 2)
            return;

        /**
         * @var int         $i
         * @var GameUpdater $task
         */
        foreach ($this->Tasks as $i => $task)
        {
            if ($i !== $taskID)
                continue;

            LimGam::GetInstance()->getScheduler()->cancelTask($task->getTaskId());
            unset($this->Tasks[$i]);
        }
    }



    /**
     * @param IGamEvent $event
     */
    public function AddEvent(IGamEvent $event): void
    {
        if ($event->GetGame() !== $this->Name)
            throw new InvalidArgumentException("Trying to add an event with incompatible game type is not acceptable.");

        $this->Events[$event->GetEvent()] = $event;
    }



    /**
     * @param string $event
     * @return IGamEvent|null
     */
    public function GetEvent(string $event): ?IGamEvent
    {
        return ($this->Events[$event] ?? null);
    }



    /**
     * @return array|IGamEvent[]
     */
    public function GetEvents(): array
    {
        return $this->Events;
    }



}