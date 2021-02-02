<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game;


use Countable;
use Exception;
use InvalidArgumentException;
use Performance\Performance;
use Throwable;
use LimGam\LimGam;
use LimGam\Game\Event\IGamEvent;
use LimGam\Task\Game\ExpiredLinksUpdater;
use LimGam\Task\Game\GameUpdater;


/**
 * @author  RomnSD
 * @package LimGam\Game
 */
class Game implements Countable
{



    /** @var string */
    protected $name;

    /** @var Arena[] */
    protected $arenas;

    /** @var string[] */
    protected $links;

    /** @var IGamEvent[] */
    protected $events;

    /** @var GameUpdater[] */
    protected $tasks;

    /** @var ExpiredLinksUpdater */
    protected $linksUpdater;



    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name         = $name;
        $this->arenas       = [];
        $this->links        = [];
        $this->tasks        = [(new GameUpdater($this, 0))->start()];
        $this->linksUpdater = (new ExpiredLinksUpdater($this))->start();
    }



    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }



    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->arenas);
    }



    /**
     * @return array
     */
    public function getArenas(): array
    {
        return $this->arenas;
    }



    /**
     * @param string $player
     * @param string $arenaID
     */
    public function link(string $player, string $arenaID): void
    {
        $this->links[$player] = [$arenaID, 30];
    }



    /**
     * @param string $player
     */
    public function unlink(string $player): void
    {
        unset($this->links[$player]);
    }



    /**
     * @return array|string[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }



    /**
     * @return ExpiredLinksUpdater|null
     */
    public function getLinksUpdater(): ?ExpiredLinksUpdater
    {
        return $this->linksUpdater;
    }



    /**
     * @param Arena $arena
     * @throws Exception
     */
    public function addArena(Arena $arena): void
    {
        if (isset($this->arenas[$arena->getID()]))
            throw new Exception("Cannot add twice an arena.");

        $this->arenas[$arena->getID()] = $arena;

        $total = $this->count();
        $div   = (int) ($total / 5);

        if ((int) ($total % 5) === 0 && $total > 0)
            $this->tasks[$div] = (new GameUpdater($this, $div))->start();
    }



    /**
     * @param string $arenaID
     */
    public function removeArena(string $arenaID): void
    {
        if (!isset($this->arenas[$arenaID]))
            return;

        try
        {
            $this->arenas[$arenaID]->close();
        }
        catch (Throwable $e)
        {
            LimGam::GetInstance()->getLogger()->logException($e);
        }
        finally
        {
            unset($this->arenas[$arenaID]);
        }

    }



    /**
     * @param string $id
     * @return Arena|null
     */
    public function getArena(string $id): ?Arena
    {
        return ($this->arenas[$id] ?? null);
    }



    /**
     * @param string|null $player
     * @return Arena|null
     */
    public function getFreeArena(string $player = null): ?Arena
    {
        if ($player && isset($this->links[$player]))
            return $this->getArena($this->links[$player][0]);

        foreach ($this->arenas as $arena)
        {
            if ($arena->getFreeSlots() > 0 && $arena->isJoinable())
                return $arena;
        }

        return null;
    }



    /**
     * @param int $taskID
     */
    public function removeTask(int $taskID): void
    {
        if (count($this->tasks) < 2)
            return;

        /** @var GameUpdater $task */
        foreach ($this->tasks as $i => $task)
        {
            if ($task->getTaskId() !== $taskID)
                continue;

            LimGam::GetInstance()->getScheduler()->cancelTask($taskID);
            unset($this->tasks[$taskID]);

            return;
        }
    }



    /**
     * @param int $taskID
     */
    public function removeTaskByChunkID(int $taskID): void
    {
        if (count($this->tasks) < 2)
            return;

        /**
         * @var int         $i
         * @var GameUpdater $task
         */
        foreach ($this->tasks as $i => $task)
        {
            if ($i !== $taskID)
                continue;

            LimGam::GetInstance()->getScheduler()->cancelTask($task->getTaskId());
            unset($this->tasks[$i]);

            return;
        }
    }



    /**
     * @param IGamEvent $event
     */
    public function addEvent(IGamEvent $event): void
    {
        if ($event->getGame() !== $this->name)
            throw new InvalidArgumentException("Trying to add an event with incompatible game type is not acceptable.");

        $this->events[$event->getEvent()] = $event;
    }



    /**
     * @param string $event
     * @return IGamEvent|null
     */
    public function getEvent(string $event): ?IGamEvent
    {
        return ($this->events[$event] ?? null);
    }



    /**
     * @param string $event
     */
    public function removeEvent(string $event): void
    {
        if (isset($this->events[$event]))
            $this->events[$event]->unregister();

        unset($this->events[$event]);
    }



    /**
     * @return array|IGamEvent[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }





}