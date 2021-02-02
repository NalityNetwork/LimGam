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
    protected $arena;

    /** @var Party|null */
    protected $party;

    /** @var int */
    protected $status;

    /** @var Team */
    protected $team;

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

        $this->status = static::STATUS_BUSY;
    }



    /**
     * @param Arena|null      $arena
     * @param int        $status
     * @param Party|null $party
     * @return $this
     * @throws Exception
     */
    public function sendTo(?Arena $arena, int $status = InGame::STATUS_BUSY): self
    {
        if (!$arena)
        {
            if ($this->arena)
                (new PlayerQuitArena($this))->call();

            $this->setTeam(null);

            $this->arena  = $arena;
            $this->status = $status;

            return $this;
        }


        if ($arena->isClosed() || $arena->isJoinable() === false && $status === static::STATUS_ALIVE)
            throw new InvalidArgumentException("Cannot join to this arena.");

        $this->arena  = $arena;
        $this->status = $status;

        if (!$arena->processSession($this))
        {
            $this->sendTo(null, static::STATUS_BUSY);
            throw new Exception("Unknown error has occurred while creating a new session.");
        }

        if (!$this->getTeam())
            throw new Exception();


        if ($this->ownsAParty())
        {
            foreach ($this->party->getMembers() as $member)
            {
                $this->arena->getGame()->link($member, $this->arena->getID());
                $this->team->addReservation($member);
            }
        }

        (new PlayerJoinArena($this))->call();

        return $this;
    }



    /**
     * @return Arena
     */
    public function getArena(): ?Arena
    {
        return $this->arena;
    }



    /**
     * @param Party|null $party
     */
    public function setParty(?Party $party): void
    {
        if (($this->party instanceof Party) && $this->party !== $party)
            $this->party->disband();

        $this->party = $party;
    }



    /**
     * @return Party|null
     */
    public function getParty(): ?Party
    {
        return $this->party;
    }



    /**
     * @return bool
     */
    public function ownsAParty(): bool
    {
        if ($this->party)
            return ($this->party->getOwner() === $this->getName());

        return false;
    }



    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        if (!$this->team)
            return;

        $this->status = $status;
        $this->team->updateStatus();
    }



    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }



    /**
     * @param Team|null $team
     */
    public function setTeam(?Team $team): void
    {
        if ($team && !$team->isMember($this->getName()))
            return;

        if ($this->team)
            $this->team->removeMember($this->getName());

        $this->team = $team;
    }



    /**
     * @return Team|null
     */
    public function getTeam(): ?Team
    {
        return $this->team;
    }


    public function inGame(): bool
    {
        return ($this->arena && $this->team);
    }



    /**
     * @return bool
     */
    public function isAlive(): bool
    {
        return $this->status === static::STATUS_ALIVE;
    }



    /**
     * @return bool
     */
    public function isSpectating(): bool
    {
        return $this->status === static::STATUS_SPECTATING;
    }



    /**
     * @return bool
     */
    public function isBusy(): bool
    {
        return $this->status === static::STATUS_BUSY;
    }



    public function close(): void
    {
        $this->team->removeMember($this->getName());
    }



    public function __destruct()
    {
        if ($this->arena)
            $this->close();
    }



}