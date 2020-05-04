<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game\Team;


use Countable;
use InvalidArgumentException;
use LimGam\Game\Session\InGame;
use LimGam\LimGam;


/**
 * @author  RomnSD
 * @package LimGam\Game\Team
 */
class Team implements Countable
{



    /** @var string */
    protected $Name;

    /** @var string */
    protected $Color;

    /** @var bool */
    protected $IsExternal;

    /** @var int */
    protected $Size;

    /** @var InGame[] */
    protected $Members;

    /** @var string[] */
    protected $Reservations;



    /**
     * @param string $name
     * @param string $color
     * @param int    $size
     * @param bool   $external
     */
    public function __construct(string $name, string $color, bool $external, int $size)
    {
        if ($size < 1)
            throw new InvalidArgumentException("Team size must be greater than zero.");

        $this->Name         = $name;
        $this->Color        = $color;
        $this->IsExternal   = $external;
        $this->Size         = $size;
        $this->Members      = [];
        $this->Reservations = [];
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
     * @return int
     */
    public function GlobalCount(): int
    {
        return ($this->Count() + count($this->Reservations));
    }



    /**
     * @return int
     */
    public function GetFreeSlots(): int
    {
        return ($this->Size - $this->GlobalCount());
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
    public function AddMember(InGame $session): bool
    {
        if (isset($this->Members[$session->GetName()]))
            return false;

        if ($session->GetTeam())
            return false;

        if ($session->IsSpectating() && $this->CountInGame())
            return false;

        if (!$this->IsExternal)
        {
            if ($this->IsFull() && !isset($this->Reservations[$session->GetName()]))
                return false;

            unset($this->Reservations[$session->GetName()]);
        }

        $this->Members[$session->GetName()] = $session;
        $this->Members[$session->GetName()]->SetTeam($this);

        return true;
    }



    /**
     * @param string $player
     * @param string $reason
     * @noinspection PhpUnusedParameterInspection
     */
    public function RemoveMember(string $player, string $reason = "unknown"): void
    {
        if ($this->IsMember($player))
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
     * @param bool   $inGame
     */
    public function Message(string $message, bool $inGame = false): void
    {
        foreach ($this->Members as $session)
        {
            if ($session->IsSpectating() && $inGame)
                continue;

            $session->GetPlayer()->sendMessage($message);
        }
    }



    /**
     * @param string $message
     * @param bool   $inGame
     */
    public function Tip(string $message, bool $inGame = false): void
    {
        foreach ($this->Members as $session)
        {
            if ($session->IsSpectating() && $inGame)
                continue;

            $session->GetPlayer()->sendTip($message);
        }
    }



    /**
     * @param string $message
     * @param string $subtitle
     * @param bool   $inGame
     */
    public function Popup(string $message, string $subtitle = "", bool $inGame = false): void
    {
        foreach ($this->Members as $session)
        {
            if ($session->IsSpectating() && $inGame)
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
     * @return int
     */
    public function Count(): int
    {
        return count($this->Members);
    }



    /**
     * Return how many players are alive in the team
     * @return int
     */
    public function CountInGame(): int
    {
        if ($this->IsExternal)
            return 0;

        if ($this->IsSolo())
            return (int) ($this->Members === [] ? false : current($this->Members)->IsAlive());

        $count = 0;

        foreach ($this->Members as $player)
            if ($player->IsAlive())
                $count++;

        return $count;
    }



    /**
     * @return bool
     */
    public function IsFull(): bool
    {
        return (($this->Count() + count($this->Reservations)) === $this->Size);
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
        if ($this->IsSolo() && $count !== 1 || $this->IsFull() || ($this->Size - $this->GlobalCount()) < $count)
            return false;

        return true;
    }



    /**
     * @param string ...$players
     * @return bool
     */
    public function AddReservation(string...$players): bool
    {
        if (!$this->CanReserveSpace(count($players)))
            return false;

        foreach ($players as $player)
        {
            if ($this->IsMember($player))
                continue;

            $this->Reservations[$player] = time();
        }

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
        {
            if (isset($this->Reservations[$player]))
                unset($this->Reservations[$player]);
        }
    }



    public function __destruct()
    {
        foreach ($this->Members as $member)
            $this->RemoveMember($member->GetName(), "Team::__destruct() was called.");
    }



}