<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game\Session;


use Exception;
use InvalidArgumentException;
use LimGam\Game\Arena;
use LimGam\Game\Event\Events\Player\PlayerJoinArena;
use LimGam\Game\Event\Events\Player\PlayerQuitArena;
use LimGam\Game\Party;
use LimGam\Game\Team\Team;
use LimGam\LimGam;
use pocketmine\Player;


/**
 * @author  RomnSD
 * @package LimGam\Game\Session
 */
class InGame extends LimSession
{



    /** @var Arena */
    protected $Arena;

    /** @var Party|null */
    protected $Party;

    /** @var int */
    protected $Status;

    /** @var Team */
    protected $Team;

    /** @var int Player is alive */
    public const STATUS_ALIVE      = 100;
    /** @var int Player is spectating */
    public const STATUS_SPECTATING = 102;
    /** @var int Player is not alive but neither is spectating, use this in special cases */
    public const STATUS_BUSY       = 104;



    /**
     * @param Player $player
     */
    public function __construct(Player $player)
    {
        parent::__construct($player);

        $this->Status = static::STATUS_BUSY;
    }



    /**
     * @param Arena|null      $arena
     * @param int        $status
     * @param Party|null $party
     * @return $this
     * @throws Exception
     */
    public function SendTo(?Arena $arena, int $status = InGame::STATUS_BUSY): self
    {
        if (!$arena)
        {
            if ($this->Arena)
                (new PlayerQuitArena($this))->call();

            $this->SetTeam(null);

            $this->Arena  = $arena;
            $this->Status = $status;

            return $this;
        }


        if ($arena->IsClosed() || $arena->IsJoinable() === false && $status === static::STATUS_ALIVE)
            throw new InvalidArgumentException("Cannot join to this arena.");

        $this->Arena  = $arena;
        $this->Status = $status;

        if (!$arena->ProcessSession($this))
        {
            $this->SendTo(null, static::STATUS_BUSY);
            throw new Exception("Unknown error has occurred while creating a new session.");
        }

        if (!$this->GetTeam())
            throw new Exception();


        if ($this->OwnsAParty())
        {
            foreach ($this->Party->GetMembers() as $member)
            {
                $this->Arena->GetGame()->Link($member, $this->Arena->GetID());
                $this->Team->AddReservation($member);
            }
        }

        (new PlayerJoinArena($this))->call();

        return $this;
    }



    /**
     * @return Arena
     */
    public function GetArena(): ?Arena
    {
        return $this->Arena;
    }



    /**
     * @param Party|null $party
     */
    public function SetParty(?Party $party): void
    {
        if (($this->Party instanceof Party) && $this->Party !== $party)
            $this->Party->Disband();

        $this->Party = $party;
    }



    /**
     * @return Party|null
     */
    public function GetParty(): ?Party
    {
        return $this->Party;
    }



    /**
     * @return bool
     */
    public function OwnsAParty(): bool
    {
        if ($this->Party)
            return ($this->Party->GetOwner() === $this->GetName());

        return false;
    }



    /**
     * @param int $status
     */
    public function SetStatus(int $status): void
    {
        if (!$this->Team)
            return;

        $this->Status = $status;
        $this->Team->UpdateStatus();
    }



    /**
     * @return int
     */
    public function GetStatus(): int
    {
        return $this->Status;
    }



    /**
     * @param Team|null $team
     */
    public function SetTeam(?Team $team): void
    {
        if ($team && !$team->IsMember($this->GetName()))
            return;

        if ($this->Team)
            $this->Team->RemoveMember($this->GetName());

        $this->Team = $team;
    }



    /**
     * @return Team|null
     */
    public function GetTeam(): ?Team
    {
        return $this->Team;
    }


    public function InGame(): bool
    {
        return ($this->Arena && $this->Team);
    }



    /**
     * @return bool
     */
    public function IsAlive(): bool
    {
        return $this->Status === static::STATUS_ALIVE;
    }



    /**
     * @return bool
     */
    public function IsSpectating(): bool
    {
        return $this->Status === static::STATUS_SPECTATING;
    }



    /**
     * @return bool
     */
    public function IsBusy(): bool
    {
        return $this->Status === static::STATUS_BUSY;
    }



    public function Close(): void
    {
        $this->Team->RemoveMember($this->GetName());
    }



    public function __destruct()
    {
        if ($this->Arena)
            $this->Close();
    }



}