<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game;


use Exception;
use InvalidArgumentException;
use Throwable;
use LimGam\Game\Event\Events\Arena\GameOver;
use LimGam\Game\Map\Map;
use LimGam\Game\Session\InGame;
use LimGam\Game\Team\Team;
use LimGam\LimGam;


/**
 * @author  RomnSD
 * @package LimGam\Game
 */
abstract class Arena
{



    /** @var string */
    protected $arenaID;

    /** @var Game */
    protected $game;

    /** @var array */
    protected $config;

    /** @var int */
    protected $status;

    /** Team[] */
    protected $teams;

    /** @var bool */
    protected $joinable;

    /** @var string */
    protected $teamClass;

    /** @var int */
    protected $timeout;

    /** @var int */
    protected $countdownToStart;

    /** @var int */
    protected $countdownArenaFull;

    /** @var int */
    protected $countdownToReset;

    /** @var bool */
    protected $autoMapReset;

    /** @var int */
    protected $playersCountToStart;

    /** @var int */
    protected $teamSize;

    /** @var int */
    protected $teamsLimit;

    /** @var int */
    protected $maxPlayersInArena;

    /** @var bool */
    protected $soloMode;

    /** @var Team|null */
    protected $winner;

    /** @var int */
    protected $countdown;

    /** @var Map */
    protected $map;

    /** @var int */
    protected $lastEvent;

    /** @var bool */
    protected $closed = false;

    /** @var int */
    protected static $arenaCounter = 0;


    ####################################################
    # Arena status                                     #
    ####################################################
    /** @var int */
    public const STATUS_WAITING = 2;

    /** @var int */
    public const STATUS_BEGINNING = 4;

    /** @var int */
    public const STATUS_RUNNING = 6;

    /** @var int */
    public const STATUS_RESETTING = 8;


    ####################################################
    # Internal events                                  #
    ####################################################
    /** @var int */
    protected const STATUS_HAS_CHANGED = 10;

    /** @var int */
    protected const MAP_HAS_BEEN_ADDED = 12;

    /** @var array Default config template */
    public const CONFIG = [
        "Timeout"             => 0,
        "CountdownToStart"    => 0,
        "CountdownArenaFull"  => 0,
        "CountdownToReset"    => 0,
        //"AutoMapReset"        => false, //todo: implement
        "PlayersCountToStart" => 0,
        "TeamSize"            => 0,
        "Teams"               => []
    ];



    /**
     * @param string $arenaID
     * @param Game   $game
     * @param array  $config
     * @param string $teamClass
     * @throws Exception
     */
    public function __construct(string $arenaID, Game $game, array $config, string $teamClass = Team::class)
    {

        if (!is_a($teamClass, Team::class, true))
            throw new InvalidArgumentException("The class must be or extend " . Team::class);

        self::CheckConfig($config);

        $this->arenaID             = $arenaID;
        $this->game                = $game;
        $this->status              = Arena::STATUS_RESETTING;
        $this->config              = $config;
        $this->teams               = [];
        $this->joinable            = false;
        $this->teamClass           = $teamClass;
        $this->timeout             = $config["Timeout"];
        $this->countdownToStart    = $config["CountdownToStart"];
        $this->countdownArenaFull  = $config["CountdownArenaFull"];
        $this->countdownToReset    = $config["CountdownToReset"];
        $this->autoMapReset        = true;//$config["AutoMapReset"];
        $this->playersCountToStart = $config["PlayersCountToStart"];
        $this->teamSize            = $config["TeamSize"];
        $this->soloMode            = ($this->teamSize === 1);


        foreach ($config["Teams"] as $name => $team)
        {
            try
            {
                $this->teamsLimit++;
                $this->addTeam(new $this->teamClass((string) $name, $team[0], $team[1], $this->teamSize, $this->arenaID));
            }
            catch (Throwable $e)
            {
                LimGam::GetInstance()->getLogger()->debug($e->getMessage());
                continue;
            }
        }

        $this->maxPlayersInArena = ($this->teamsLimit * $this->teamSize);

        $this->reset();
    }



    /**
     * @param array $config
     * @throws Exception
     */
    public static function checkConfig(array $config): void
    {
        foreach (static::CONFIG as $i => $value)
        {
            if (!isset($config[$i]) || gettype($config[$i]) !== gettype($value))
                throw new Exception("Invalid arena configuration.");
        }

        if ($config["Teams"] === [])
            throw new Exception("Team list cannot be empty...");

        $val = ["string", "boolean"];

        foreach ($config["Teams"] as $name => $team)
        {
            if (!is_array($team) || count($team) < 2)
                throw new Exception("Invalid team data in $name.");

            foreach ($val as $i => $v)
            {
                if (gettype($team[$i]) !== $v)
                    throw new Exception();
            }
        }
    }



    /**
     * @return bool
     * @throws Exception
     */
    public function reset(): bool
    {

        if ($this->status !== static::STATUS_RESETTING)
            return false;

        foreach ($this->teams as $team)
            $team->cleanUp();

        if ($this->autoMapReset)
            $this->map = null;

        $this->status    = static::STATUS_WAITING;
        $this->joinable  = true;
        $this->winner    = null;
        $this->countdown = $this->countdownToStart;
        $this->lastEvent = static::STATUS_HAS_CHANGED;

        $this->broadcastInternalEvent($this->lastEvent);
        return true;
    }



    /**
     * @param Map|null $map
     * @throws Exception
     */
    public function setMap(Map $map = null): void
    {
        if ($map && $this->status === Arena::STATUS_RUNNING)
            throw new Exception("Cannot change map while the arena is running.");

        $this->map       = $map;
        $this->lastEvent = Arena::MAP_HAS_BEEN_ADDED;

        $this->broadcastInternalEvent($this->lastEvent);
    }



    /**
     * @return Map|null
     */
    public function getMap(): ?Map
    {
        return $this->map;
    }



    /**
     * @param Team $team
     * @param bool $forceAdd
     * @throws Exception
     */
    public function addTeam(Team $team, bool $forceAdd = false): void
    {
        if (isset($this->teams[$team->getName()]))
            throw new Exception("Cannot add twice a team in the same arena.");

        if (!is_a($team, $this->teamClass, true))
            throw new Exception("Team object does not match the arena team class.");

        if (!$team->isExternal())
        {
            if (count($this->teams) >= $this->teamsLimit)
                throw new Exception("Cannot add more teams, team limit reached.");

            if ($this->getStatus() === static::STATUS_RUNNING && !$forceAdd)
                throw new Exception("Cannot add teams while match is in progress.");
        }

        $this->teams[$team->getName()] = $team;
    }



    /**
     * @param string $name
     * @return Team|null
     */
    public function getTeam(string $name): ?Team
    {
        return ($this->teams[$name] ?? null);
    }



    /**
     * @param string $name
     */
    public function removeTeam(string $name): void
    {
        if (isset($this->teams[$name]))
            unset($this->teams[$name]);
    }



    /**
     * @param array  $mates
     * @param string $player
     * @return Team|null
     */
    public function findFreeTeam(string $player = "", bool $external = false, array $mates = []): ?Team
    {
        /** @var Team $team */
        foreach ($this->teams as $team)
        {
            if ($team->isExternal() !== $external)
                continue;

            if ($mates === [])
            {
                if ($player && $team->hasReservation($player))
                    return $team;

                if ($team->getFreeSlots())
                    return $team;
            }
            else
            {
                if ($team->canReserveSpace(count($mates) + 1))
                    return $team;
            }

        }

        return null;
    }



    /**
     * @param InGame    $session
     * @param Team|null $team
     * @return bool
     */
    public function addSpectator(InGame $session, Team $team = null): bool
    {
        if (!$team)
        {
            $team = $this->findFreeTeam($session->getName(), true);

            if ($team)
                $team->addReservation($session->getName());
        }

        if (!$team)
            return false;

        if ($this->getTeam($team->getName()) === null)
        {
            try
            {
                $this->addTeam($team);
            }
            catch (Throwable $e)
            {
                return false;
            }
        }

        return $team->addMember($session);
    }



    /**
     * @return int
     */
    public function getFreeSlots(): int
    {
        if (!$this->joinable)
            return 0;
        
        $slots = 0;

        foreach ($this->teams as $team)
            $slots += $team->getFreeSlots();

        return $slots;
    }



    /**
     * @return array
     */
    public function getTeams(): array
    {
        return $this->teams;
    }



    /**
     * @return bool
     */
    public function isJoinable(): bool
    {
        return $this->joinable;
    }



    /**
     * @return Team|null
     */
    public function getWinner(): ?Team
    {
        return $this->winner;
    }



    /**
     * @param bool $includeExternal
     * @return InGame[]
     */
    public function getSessions(bool $includeExternal): array
    {
        $sessions = [];

        foreach ($this->teams as $team)
        {
            if ($team->isExternal() && !$includeExternal)
                continue;

            $sessions += $team->getMembers();
        }

        return $sessions;
    }



    /**
     * @return string
     */
    public static function generateRandomID(): string
    {
        return (str_shuffle("ABC") . static::$arenaCounter++);
    }



    /**
     * @return string
     */
    public function getID(): string
    {
        return $this->arenaID;
    }



    /**
     * @return Game
     */
    public function getGame(): Game
    {
        return $this->game;
    }



    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }



    /**
     * @return int
     */
    public function getCountdown(): int
    {
        return $this->countdown;
    }



    /**
     * @param int|null $status
     * @return int
     */
    public function getStatus(int $status = null): int
    {
        if ($status)
            return (int) ((($this->status & (Arena::STATUS_WAITING | Arena::STATUS_BEGINNING | Arena::STATUS_RUNNING | Arena::STATUS_RESETTING)) & $status) === $status);

        return $this->status;
    }



    /**
     * @param int $currentTick
     * @throws Exception
     */
    public function update(int $currentTick = 0)
    {
        if ($this->closed)
            return;

        if ($this->countdown < 0)
            $this->countdown = 0;

        if ($this->status === static::STATUS_WAITING)
        {
            $this->statusWaiting();
            return;
        }

        if ($this->status === static::STATUS_BEGINNING)
        {
            $this->statusBeginning();
            return;
        }

        if ($this->status === static::STATUS_RUNNING)
        {
            $this->statusRunning();
            return;
        }

        if ($this->status === static::STATUS_RESETTING)
        {
            $this->statusResetting();
            return;
        }
    }



    /**
     * @return int
     */
    public function getCountInGeneral(): int
    {
        $count = 0;

        foreach ($this->teams as $team)
            $count += $team->countInGame();

        return $count;
    }



    /**
     * @return Team[]
     */
    public function getRemainingTeams(): array
    {
        $list = [];

        foreach ($this->teams as $team)
            if ($team->countInGame())
                $list[] = $team;

        return $list;
    }



    /**
     * @throws Exception
     * @internal
     */
    protected function statusWaiting()
    {
        if ($this->getCountInGeneral() >= $this->playersCountToStart)
        {
            $this->status    = Arena::STATUS_BEGINNING;
            $this->lastEvent = Arena::STATUS_HAS_CHANGED;

            $this->broadcastInternalEvent($this->lastEvent);
        }
    }



    /**
     * @throws Exception
     * @internal
     */
    protected function statusBeginning()
    {
        if ($this->getCountInGeneral() < $this->playersCountToStart)
        {
            $this->status    = static::STATUS_WAITING;
            $this->countdown = $this->countdownToStart;
            $this->lastEvent = static::STATUS_HAS_CHANGED;

            $this->broadcastInternalEvent($this->lastEvent);

            return;
        }

        if ($this->getCountInGeneral() === $this->maxPlayersInArena)
        {
            if ($this->countdown > $this->countdownArenaFull)
                $this->countdown = $this->countdownArenaFull;
        }

        if ($this->countdown-- === 0)
        {
            $this->status    = static::STATUS_RUNNING;
            $this->lastEvent = static::STATUS_HAS_CHANGED;

            $this->broadcastInternalEvent($this->lastEvent);
        }
    }



    /**
     * @throws Exception
     * @internal
     */
    protected function statusRunning()
    {
        if ($this->countdown-- === 0)
        {
            $this->status    = static::STATUS_RESETTING;
            $this->countdown = $this->countdownToReset;
            $this->lastEvent = static::STATUS_HAS_CHANGED;

            $this->broadcastInternalEvent($this->lastEvent);
        }

        if ($this->status === static::STATUS_RESETTING)
            (new GameOver($this))->call();
    }



    /**
     * @throws Exception
     * @internal
     */
    protected function statusResetting()
    {
        if ($this->countdown-- === 0)
            $this->reset();
    }



    /**
     * @param int $event
     * @return bool
     * @throws Exception
     * @internal
     */
    protected function broadcastInternalEvent(int $event)
    {
        if ($event === static::STATUS_HAS_CHANGED)
        {
            if ($this->status === static::STATUS_RUNNING && $this->map === null)
                throw new Exception("Cannot start a game without a map.");

            if ($this->status === static::STATUS_RUNNING)
            {
                $this->start();
                return true;
            }

            if ($this->status === static::STATUS_RESETTING)
            {
                $this->end();
                return true;
            }
        }

        return true;
    }



    /**
     * @internal
     */
    protected function start()
    {
        $this->countdown = $this->timeout;
    }



    /**
     * @internal
     */
    protected function end()
    {
        //...
    }



    /**
     * @param InGame $session
     * @return bool
     */
    public abstract function processSession(InGame $session): bool;



    /**
     * Logical function to set and get the winner team of the match.
     * Use this function with "$this->winner" variable.
     * @return mixed
     * @internal
     */
    protected abstract function checkWinner();



    /**
     * Closes the arena
     */
    public function close(): void
    {
        if ($this->closed)
            return;

        foreach ($this->getSessions(true) as $session)
            LimGam::GetGameManager()->removeSession($session->getName());

        if ($this->map && $this->map->getLevelObject())
            $this->map->setLevelObject(null, true);

        $this->map = null;
        $this->game->removeArena($this->getID());
    }



    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }



}