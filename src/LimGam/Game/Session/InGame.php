<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game\Session;


use Exception;
use LimGam\Game\Arena;
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
     * @param Arena  $arena
     * @param Party  $party
     * @param int    $status
     * @throws Exception
     */
    public function __construct(Player $player, Arena $arena, ?Party $party, int $status)
    {
        parent::__construct($player);

        $this->Arena  = $arena;
        $this->Party  = $party;
        $this->Status = $status;

        if ($arena->ProcessSession($this, $status) === false)
            throw new Exception("Unknown error has occurred while creating a new session.");

        #=====================#
        # Simple party system #
        #=====================#
        if ($party)
        {

            if ($party->PlayTogether() && $this->Team->GetFreeSlots() < count($party->GetMembers()))
                throw new Exception("The team you have joined has not enough slots for your party.");

            $pMembers = $party->GetMembers();

            if (!$this->Team->AddReservation(...$pMembers))
            {
                if ($party->PlayTogether())
                    throw new Exception("There are not available teams in this match.");

                foreach ($pMembers as $member)
                {
                    $member = LimGam::GetInstance()->getServer()->getPlayerExact($member);

                    if (!($member instanceof Player))
                        continue;

                    LimGam::GetGameManager()->AddSession($member, $arena, null);
                }
            }
            else
            {
                foreach ($pMembers as $member)
                    $arena->GetGame()->Link($member, $arena->GetID());
            }
        }

    }



    /**
     * @return Arena
     */
    public function GetArena(): Arena
    {
        return $this->Arena;
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
        $this->Status = $status;
    }



    /**
     * @return int
     */
    public function GetStatus(): int
    {
        return $this->Status;
    }



    /**
     * @param Team $team
     * @param bool $forceSet
     */
    public function SetTeam(Team $team, bool $forceSet = false): void
    {
        if (!$team->IsMember($this->GetName()))
            return;

        if ($this->Team)
        {
            if (!$forceSet)
                return;

            $this->Team->RemoveMember($this->GetName());
        }

        $this->Team = $team;
    }



    /**
     * @return Team|null
     */
    public function GetTeam(): ?Team
    {
        return $this->Team;
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
        $this->Team->RemoveMember($this->Player->getName());
    }



    public function __destruct()
    {
        if ($this->Arena)
            $this->Close();
    }



}