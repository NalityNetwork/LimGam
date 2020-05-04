<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game;


use Exception;
use InvalidArgumentException;
use LimGam\Game\Event\Events\Arena\GameOver;
use LimGam\Game\Map\Map;
use LimGam\Game\Session\InGame;
use LimGam\Game\Team\Team;
use LimGam\LimGam;
use Throwable;


/**
 * @author  RomnSD
 * @package LimGam\Game
 */
abstract class Arena
{



    /** @var string */
    protected $ArenaID;

    /** @var Game */
    protected $Game;

    /** @var array */
    protected $Config;

    /** @var int */
    protected $Status;

    /** Team[] */
    protected $Teams;

    /** @var bool */
    protected $Joinable;

    /** @var string */
    protected $TeamClass;

    /** @var int */
    protected $Timeout;

    /** @var int */
    protected $CountdownToStart;

    /** @var int */
    protected $CountdownArenaFull;

    /** @var int */
    protected $CountdownToReset;

    /** @var int */
    protected $PlayersCountToStart;

    /** @var int */
    protected $TeamSize;

    /** @var int */
    protected $TeamsLimit;

    /** @var int */
    protected $MaxPlayersInArena;

    /** @var bool */
    protected $SoloMode;

    /** @var Team|null */
    protected $Winner;

    /** @var int */
    protected $Countdown;

    /** @var Map */
    protected $Map;

    /** @var int */
    protected $LastEvent;

    /** @var bool */
    protected $Closed = false;

    /** @var int */
    protected static $ArenaCounter = 0;


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

    /** @var array */
    public const CONFIG = [
        "Timeout"             => 0,
        "CountdownToStart"    => 0,
        "CountdownArenaFull"  => 0,
        "CountdownToReset"    => 0,
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
            throw new InvalidArgumentException();

        self::CheckConfig($config);

        $this->ArenaID             = $arenaID;
        $this->Game                = $game;
        $this->Status              = Arena::STATUS_RESETTING;
        $this->Config              = $config;
        $this->Teams               = [];
        $this->Joinable            = false;
        $this->TeamClass           = $teamClass;
        $this->Timeout             = $config["Timeout"];
        $this->CountdownToStart    = $config["CountdownToStart"];
        $this->CountdownArenaFull  = $config["CountdownArenaFull"];
        $this->CountdownToReset    = $config["CountdownToReset"];
        $this->PlayersCountToStart = $config["PlayersCountToStart"];
        $this->TeamSize            = $config["TeamSize"];
        $this->SoloMode            = ($this->TeamSize === 1);


        foreach ($config["Teams"] as $name => $team)
        {
            try
            {
                $this->TeamsLimit++;
                $this->AddTeam(new $this->TeamClass($name, $team[0], $team[1], $this->TeamSize));
            }
            catch (Exception $e)
            {
                break;
            }
        }

        $this->MaxPlayersInArena = ($this->TeamsLimit * $this->TeamSize);

        $this->Reset();
    }



    /**
     * @param array $config
     * @throws Exception
     */
    public static function CheckConfig(array $config): void
    {
        foreach (static::CONFIG as $i => $value)
        {
            if (!isset($config[$i]) || gettype($config[$i]) !== gettype($value))
                throw new Exception("Invalid arena configuration.");
        }

        if ($config["Teams"] === [])
            throw new Exception("Team list cannot be empty...");

        $val = [0 => "string", 1 => "boolean"];

        foreach ($config["Teams"] as $name => $team)
        {
            if (!is_array($team) || count($team) < 2)
                throw new Exception("");

            foreach ($val as $i => $v)
                if (gettype($team[$i]) !== $v)
                    throw new Exception();
        }
    }



    /**
     * @return bool
     * @throws Exception
     */
    public function Reset(): bool
    {

        if ($this->Status !== static::STATUS_RESETTING)
            return false;

        foreach ($this->Teams as $team)
            $team->CleanUp();

        $this->Status    = static::STATUS_WAITING;
        $this->Joinable  = true;
        $this->Winner    = null;
        $this->Countdown = $this->CountdownToStart;
        $this->LastEvent = static::STATUS_HAS_CHANGED;

        $this->BroadcastInternalEvent($this->LastEvent);
        return true;
    }



    /**
     * @param Map|null $map
     * @throws Exception
     */
    public function SetMap(Map $map = null): void
    {
        if ($map && $this->Status === Arena::STATUS_RUNNING)
            throw new Exception("Cannot change map while the arena is running.");

        $this->Map       = $map;
        $this->LastEvent = Arena::MAP_HAS_BEEN_ADDED;

        $this->BroadcastInternalEvent($this->LastEvent);
    }



    /**
     * @return Map|null
     */
    public function GetMap(): ?Map
    {
        return $this->Map;
    }



    /**
     * @param Team $team
     * @param bool $forceAdd
     * @throws Exception
     */
    public function AddTeam(Team $team, bool $forceAdd = false): void
    {
        if (isset($this->Teams[$team->GetName()]))
            throw new Exception("Cannot add twice a team in the same arena.");

        if (!is_a($team, $this->TeamClass, true))
            throw new Exception("Team object does not match the arena team class.");

        if (!$team->IsExternal())
        {
            if (count($this->Teams) >= $this->TeamsLimit)
                throw new Exception("Cannot add more teams, team limit reached.");

            if ($this->GetStatus() === static::STATUS_RUNNING && !$forceAdd)
                throw new Exception("Cannot add teams while match is in progress.");
        }

        $this->Teams[$team->GetName()] = $team;
    }



    /**
     * @param string $name
     * @return Team|null
     */
    public function GetTeam(string $name): ?Team
    {
        return ($this->Teams[$name] ?? null);
    }



    /**
     * @param string $name
     */
    public function RemoveTeam(string $name): void
    {
        if (isset($this->Teams[$name]))
            unset($this->Teams[$name]);
    }



    /**
     * @param array  $mates
     * @param string $player
     * @return Team|null
     */
    public function FindFreeTeam(string $player = "", array $mates = []): ?Team
    {
        /** @var Team $team */
        foreach ($this->Teams as $team)
        {
            if ($mates === [])
            {
                if ($player && $team->HasReservation($player))
                    return $team;

                if (!$team->IsFull())
                    return $team;
            }
            else
            {
                if ($team->CanReserveSpace(count($mates) + 1))
                    return $team;
            }

        }

        return null;
    }



    /**
     * @param string $player
     * @return Team|null
     */
    public function FindFreeExternalTeam(string $player = ""): ?Team
    {
        /** @var Team $team */
        foreach ($this->Teams as $team)
        {
            if ($team->IsExternal())
            {
                if ($player && $team->HasReservation($player))
                    return $team;

                if ($team->GetFreeSlots() > 0)
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
    public function AddSpectator(InGame $session, Team $team = null)
    {
        if (!$team)
        {
            $team = $this->FindFreeExternalTeam($session->getName());

            if ($team)
                $team->AddReservation($session->getName());
        }

        if (!$team)
            return false;

        if ($this->GetTeam($team->GetName()) === null)
        {
            try
            {
                $this->AddTeam($team);
            }
            catch (Throwable $e)
            {
                return false;
            }
        }

        return $team->AddMember($session);
    }



    /**
     * @return int
     */
    public function GetFreeSlots(): int
    {
        $slots = 0;

        foreach ($this->Teams as $team)
            $slots += $team->GetFreeSlots();

        return $slots;
    }



    /**
     * @return array
     */
    public function GetTeams(): array
    {
        return $this->Teams;
    }



    /**
     * @return bool
     */
    public function IsJoinable(): bool
    {
        return $this->Joinable;
    }



    /**
     * @return Team|null
     */
    public function GetWinner(): ?Team
    {
        return $this->Winner;
    }



    /**
     * @param bool $includeExternal
     * @return InGame[]
     */
    public function GetSessions(bool $includeExternal): array
    {
        $sessions = [];

        foreach ($this->Teams as $team)
        {
            if ($team->IsExternal() && !$includeExternal)
                continue;

            $sessions += $team->GetMembers();
        }

        return $sessions;
    }



    /**
     * @return string
     */
    public static function GenerateRandomID(): string
    {
        return (str_shuffle("ABC") . static::$ArenaCounter++);
    }



    /**
     * @return string
     */
    public function GetID(): string
    {
        return $this->ArenaID;
    }



    /**
     * @return Game
     */
    public function GetGame(): Game
    {
        return $this->Game;
    }



    /**
     * @return array
     */
    public function GetConfig(): array
    {
        return $this->Config;
    }



    /**
     * @return int
     */
    public function GetCountdown(): int
    {
        return $this->Countdown;
    }



    /**
     * @param int|null $status
     * @return int
     */
    public function GetStatus(int $status = null): int
    {
        if ($status)
            return (int) ((($this->Status & (Arena::STATUS_WAITING | Arena::STATUS_BEGINNING | Arena::STATUS_RUNNING | Arena::STATUS_RESETTING)) & $status) === $status);

        return $this->Status;
    }



    /**
     * @throws Exception
     */
    public function Update()
    {
        if ($this->Closed)
            return;

        if ($this->Countdown < 0)
            $this->Countdown = 0;

        if ($this->Status === static::STATUS_WAITING)
        {
            $this->StatusWaiting();
            return;
        }

        if ($this->Status === static::STATUS_BEGINNING)
        {
            $this->StatusBeginning();
            return;
        }

        if ($this->Status === static::STATUS_RUNNING)
        {
            $this->StatusRunning();
            return;
        }

        if ($this->Status === static::STATUS_RESETTING)
        {
            $this->StatusResetting();
            return;
        }
    }



    /**
     * @return int
     */
    public function GetCountInGeneral(): int
    {
        $count = 0;

        foreach ($this->Teams as $team)
            if (!$team->isEmpty())
                $count += $team->CountInGame();

        return $count;
    }



    /**
     * @return Team[]
     */
    public function GetRemainingTeams(): array
    {
        $list = [];

        foreach ($this->Teams as $team)
            if ($team->CountInGame())
                $list[] = $team;

        return $list;
    }



    /**
     * @throws Exception
     * @internal
     */
    protected function StatusWaiting()
    {
        if ($this->GetCountInGeneral() >= $this->PlayersCountToStart)
        {
            $this->Status    = Arena::STATUS_BEGINNING;
            $this->LastEvent = Arena::STATUS_HAS_CHANGED;

            $this->BroadcastInternalEvent($this->LastEvent);
        }
    }



    /**
     * @throws Exception
     * @internal
     */
    protected function StatusBeginning()
    {
        if ($this->GetCountInGeneral() < $this->PlayersCountToStart)
        {
            $this->Status    = static::STATUS_WAITING;
            $this->Countdown = $this->CountdownToStart;
            $this->LastEvent = static::STATUS_HAS_CHANGED;

            $this->BroadcastInternalEvent($this->LastEvent);

            return;
        }

        if ($this->GetCountInGeneral() === $this->MaxPlayersInArena)
        {
            if ($this->Countdown > $this->CountdownArenaFull)
                $this->Countdown = $this->CountdownArenaFull;
        }

        if ($this->Countdown-- === 0)
        {
            $this->Status    = static::STATUS_RUNNING;
            $this->LastEvent = static::STATUS_HAS_CHANGED;

            $this->BroadcastInternalEvent($this->LastEvent);
        }
    }



    /**
     * @throws Exception
     * @internal
     */
    protected function StatusRunning()
    {
        if ($this->Countdown-- === 0)
        {
            $this->Status    = static::STATUS_RESETTING;
            $this->Countdown = $this->CountdownToReset;
            $this->LastEvent = static::STATUS_HAS_CHANGED;

            $this->BroadcastInternalEvent($this->LastEvent);
        }

        if ($this->Status === static::STATUS_RESETTING)
            (new GameOver($this))->call();
    }



    /**
     * @throws Exception
     */
    protected function StatusResetting()
    {
        if ($this->Countdown-- === 0)
            $this->Reset();
    }



    /**
     * @param int $event
     * @return bool
     * @throws Exception
     */
    protected function BroadcastInternalEvent(int $event)
    {
        if ($event === static::STATUS_HAS_CHANGED)
        {
            if ($this->Status === static::STATUS_RUNNING && $this->Map === null)
                throw new Exception("Cannot start a game without a map.");

            if ($this->Status === static::STATUS_RUNNING)
            {
                $this->Start();
                return true;
            }

            if ($this->Status === static::STATUS_RESETTING)
            {
                $this->End();
                return true;
            }
        }

        return true;
    }



    /**
     * @internal
     */
    protected function Start()
    {
        $this->Countdown = $this->Timeout;
    }



    /**
     * @throws Exception
     * @internal
     */
    protected function End()
    {
        //...
    }



    /**
     * @param InGame $session
     * @param int    $status
     * @return bool
     */
    public abstract function ProcessSession(InGame $session, int $status): bool;



    /**
     * @return mixed
     */
    protected abstract function CheckWinner();



    public function Close(): void
    {
        if ($this->Closed)
            return;

        foreach ($this->GetSessions(true) as $session)
            LimGam::GetGameManager()->RemoveSession($session->GetName());

        if ($this->Map && $this->Map->GetLevelObject())
            $this->Map->SetLevelObject(null, true);

        $this->Game->RemoveArena($this->GetID());
    }



    /**
     * @return bool
     */
    public function IsClosed(): bool
    {
        return $this->Closed;
    }



}