<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game\Team;


use Exception;
use InvalidArgumentException;
use LimGam\Game\Session\InGame;
use LimGam\LimGam;


/**
 * @author  RomnSD
 * @package LimGam\Game\Team
 */
class Team
{

    /** @var string */
    protected $Name;

    /** @var string */
    protected $Color;

    /** @var bool */
    protected $IsExternal;

    /** @var int */
    protected $Size;

    /** @var string */
    protected $ArenaID;

    /** @var int */
    protected $Alive = 0;

    /** @var int */
    protected $Spectators = 0;

    /** @var int */
    protected $Busy = 0;

    /** @var InGame[] */
    protected $Members = [];

    /** @var string[] */
    protected $Reservations = [];



    /**
     * @param string $name
     * @param string $color
     * @param bool   $external
     * @param int    $size
     * @param string $ArenaID
     */
    public function __construct(string $name, string $color, bool $external, int $size, string $ArenaID = "")
    {
        if ($size < 1)
            throw new InvalidArgumentException("Team size must be greater than zero.");

        $this->Name       = $name;
        $this->Color      = $color;
        $this->IsExternal = $external;
        $this->Size       = $size;
        $this->ArenaID    = $ArenaID;
    }



    /**
     * @return string
     */
    public function GetName(): string
    {
        return $this->Name;
    }



    /**
     * @return string
     */
    public function GetColor(): string
    {
        return $this->Color;
    }



    /**
     * @return bool
     */
    public function IsExternal(): bool
    {
        return $this->IsExternal;
    }



    /**
     * @return int
     */
    public function GetSize(): int
    {
        return $this->Size;
    }



    /**
     * @return string
     */
    public function GetArenaID(): string
    {
        return $this->ArenaID;
    }



    /**
     * @param bool $includeReservations
     * @return int
     */
    public function Count(bool $includeReservations = false): int
    {
        if ($includeReservations)
            return count($this->Members) + count($this->Reservations);

        return count($this->Members);
    }



    /**
     * @return int
     */
    public function GetFreeSlots(): int
    {
        return ($this->Size - $this->Count(true));
    }


    public function UpdateStatus(): void
    {
        $this->Alive      = 0;
        $this->Spectators = 0;
        $this->Busy       = 0;

        foreach ($this->Members as $member)
        {
            if ($member->IsAlive())
                $this->Alive++;

            if ($member->IsSpectating())
                $this->Spectators++;

            if ($member->IsBusy())
                $this->Busy++;
        }
    }



    /**
     * @param string $player
     * @return bool
     */
    public function IsMember(string $player): bool
    {
        return isset($this->Members[$player]);
    }



    /**
     * TODO: Error codes
     * @param InGame $session
     * @return bool
     */
    public function AddMember(InGame $session): bool //Member is not a member yet
    {

        if ($session->GetArena())
        {
            if ($session->GetArena()->GetID() !== $this->ArenaID)
                throw new InvalidArgumentException("");

            if (isset($this->Members[$session->GetName()]) || $session->GetTeam())
                return false;

            $sname = $session->GetName();

            if (!$this->IsExternal)
            {
                if ($this->IsFull() && !isset($this->Reservations[$sname]))
                    return false;

                unset($this->Reservations[$sname]);
            }

            /** @var Team $team */
            foreach ($session->GetArena()->GetTeams() as $team)
                $team->RemoveReservation($sname);

            $this->Members[$sname] = $session;
            $this->Members[$sname]->SetTeam($this);

            $this->UpdateStatus();

            return true;
        }


        throw new Exception();
    }



    /**
     * @param string $player
     */
    public function RemoveMember(string $player): void
    {
        unset($this->Members[$player]);
    }



    /**
     * @return array|InGame[]
     */
    public function GetMembers(): array
    {
        return $this->Members;
    }



    /**
     * @param string $message
     * @param int    $status
     */
    public function Message(string $message, int $status = InGame::STATUS_ALIVE): void
    {
        foreach ($this->Members as $session)
        {
            if ($session->GetStatus() !== $status)
                continue;

            $session->GetPlayer()->sendMessage($message);
        }
    }



    /**
     * @param string $message
     * @param int    $status
     */
    public function Tip(string $message, int $status = InGame::STATUS_ALIVE): void
    {
        foreach ($this->Members as $session)
        {
            if ($session->GetStatus() !== $status)
                continue;

            $session->GetPlayer()->sendTip($message);
        }
    }



    /**
     * @param string $message
     * @param string $subtitle
     * @param int    $status
     */
    public function Popup(string $message, string $subtitle = "", int $status = InGame::STATUS_ALIVE): void
    {
        foreach ($this->Members as $session)
        {
            if ($session->GetStatus() !== $status)
                continue;

            $session->GetPlayer()->sendPopup($message, $subtitle);
        }
    }



    public function CleanUp(): void
    {
        foreach ($this->Members as $session)
            LimGam::GetGameManager()->RemoveSession($session->GetName());

        $this->Reservations = [];
    }



    /**
     * Return how many players are alive in the team
     * @return int
     */
    public function CountInGame(): int
    {
        if ($this->IsExternal)
            return 0;

        return $this->Alive;
    }



    /**
     * @return bool
     */
    public function IsFull(): bool
    {
        return ($this->Count(true) === $this->Size);
    }



    /**
     * @return bool
     */
    public function IsSolo(): bool
    {
        return ($this->Size === 1);
    }



    /**
     * @return bool
     */
    public function IsEmpty(): bool
    {
        return ($this->Members === []);
    }



    /**
     * @param int $count
     * @return bool
     */
    public function CanReserveSpace(int $count): bool
    {
        if ($this->GetFreeSlots() < $count)
            return false;

        return true;
    }



    /**
     * @param string $player
     * @return bool
     */
    public function AddReservation(string $player): bool
    {
        if (!$this->GetFreeSlots() || $this->IsMember($player))
            return false;

        $this->Reservations[$player] = time();
        return true;
    }



    /**
     * @return array|string[]
     */
    public function GetReservations(): array
    {
        return $this->Reservations;
    }



    /**
     * @param string $player
     * @return bool
     */
    public function HasReservation(string $player): bool
    {
        return isset($this->Reservations[$player]);
    }



    /**
     * @param string ...$players
     */
    public function RemoveReservation(string...$players): void
    {
        foreach ($players as $player)
            unset($this->Reservations[$player]);
    }



    public function __destruct()
    {
        foreach ($this->Members as $member)
            $this->RemoveMember($member->GetName());
    }



}