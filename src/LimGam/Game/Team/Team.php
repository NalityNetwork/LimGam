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
    protected $name;

    /** @var string */
    protected $color;

    /** @var bool */
    protected $isExternal;

    /** @var int */
    protected $size;

    /** @var string */
    protected $arenaID;

    /** @var int */
    protected $alive = 0;

    /** @var int */
    protected $spectators = 0;

    /** @var int */
    protected $busy = 0;

    /** @var InGame[] */
    protected $members = [];

    /** @var string[] */
    protected $reservations = [];



    /**
     * @param string $name
     * @param string $color
     * @param bool   $external
     * @param int    $size
     * @param string $arenaID
     */
    public function __construct(string $name, string $color, bool $external, int $size, string $arenaID = "")
    {
        if ($size < 1)
            throw new InvalidArgumentException("Team size must be greater than zero.");

        $this->name       = $name;
        $this->color      = $color;
        $this->isExternal = $external;
        $this->size       = $size;
        $this->arenaID    = $arenaID;
    }



    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }



    /**
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }



    /**
     * @return bool
     */
    public function isExternal(): bool
    {
        return $this->isExternal;
    }



    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }



    /**
     * @return string
     */
    public function getArenaID(): string
    {
        return $this->arenaID;
    }



    /**
     * @param bool $includeReservations
     * @return int
     */
    public function count(bool $includeReservations = false): int
    {
        if ($includeReservations)
            return count($this->members) + count($this->reservations);

        return count($this->members);
    }



    /**
     * @return int
     */
    public function getFreeSlots(): int
    {
        return ($this->size - $this->count(true));
    }


    public function updateStatus(): void
    {
        $this->alive      = 0;
        $this->spectators = 0;
        $this->busy       = 0;

        foreach ($this->members as $member)
        {
            if ($member->isAlive())
                $this->alive++;

            if ($member->isSpectating())
                $this->spectators++;

            if ($member->isBusy())
                $this->busy++;
        }
    }



    /**
     * @param string $player
     * @return bool
     */
    public function isMember(string $player): bool
    {
        return isset($this->members[$player]);
    }



    /**
     * TODO: Error codes
     * @param InGame $session
     * @return bool
     */
    public function addMember(InGame $session): bool //Member is not a member yet
    {

        if ($session->getArena())
        {
            if ($session->getArena()->getID() !== $this->arenaID)
                throw new InvalidArgumentException("");

            if (isset($this->members[$session->getName()]) || $session->getTeam())
                return false;

            $sname = $session->getName();

            if (!$this->isExternal)
            {
                if ($this->isFull() && !isset($this->reservations[$sname]))
                    return false;

                unset($this->reservations[$sname]);
            }

            /** @var Team $team */
            foreach ($session->getArena()->getTeams() as $team)
                $team->removeReservation($sname);

            $this->members[$sname] = $session;
            $this->members[$sname]->setTeam($this);

            $this->updateStatus();

            return true;
        }


        throw new Exception();
    }



    /**
     * @param string $player
     */
    public function removeMember(string $player): void
    {
        unset($this->members[$player]);
    }



    /**
     * @return array|InGame[]
     */
    public function getMembers(): array
    {
        return $this->members;
    }



    /**
     * @param string $message
     * @param int    $status
     */
    public function message(string $message, int $status = InGame::STATUS_ALIVE): void
    {
        foreach ($this->members as $session)
        {
            if ($session->getStatus() !== $status)
                continue;

            $session->getPlayer()->sendMessage($message);
        }
    }



    /**
     * @param string $message
     * @param int    $status
     */
    public function tip(string $message, int $status = InGame::STATUS_ALIVE): void
    {
        foreach ($this->members as $session)
        {
            if ($session->getStatus() !== $status)
                continue;

            $session->getPlayer()->sendTip($message);
        }
    }



    /**
     * @param string $message
     * @param string $subtitle
     * @param int    $status
     */
    public function popup(string $message, string $subtitle = "", int $status = InGame::STATUS_ALIVE): void
    {
        foreach ($this->members as $session)
        {
            if ($session->getStatus() !== $status)
                continue;

            $session->getPlayer()->sendPopup($message, $subtitle);
        }
    }



    public function cleanUp(): void
    {
        foreach ($this->members as $session)
            LimGam::GetGameManager()->removeSession($session->getName());

        $this->reservations = [];
    }



    /**
     * Return how many players are alive in the team
     * @return int
     */
    public function countInGame(): int
    {
        if ($this->isExternal)
            return 0;

        return $this->alive;
    }



    /**
     * @return bool
     */
    public function isFull(): bool
    {
        return ($this->count(true) === $this->size);
    }



    /**
     * @return bool
     */
    public function isSolo(): bool
    {
        return ($this->size === 1);
    }



    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return ($this->members === []);
    }



    /**
     * @param int $count
     * @return bool
     */
    public function canReserveSpace(int $count): bool
    {
        if ($this->getFreeSlots() < $count)
            return false;

        return true;
    }



    /**
     * @param string $player
     * @return bool
     */
    public function addReservation(string $player): bool
    {
        if (!$this->getFreeSlots() || $this->isMember($player))
            return false;

        $this->reservations[$player] = time();
        return true;
    }



    /**
     * @return array|string[]
     */
    public function getReservations(): array
    {
        return $this->reservations;
    }



    /**
     * @param string $player
     * @return bool
     */
    public function hasReservation(string $player): bool
    {
        return isset($this->reservations[$player]);
    }



    /**
     * @param string ...$players
     */
    public function removeReservation(string...$players): void
    {
        foreach ($players as $player)
            unset($this->reservations[$player]);
    }



    public function __destruct()
    {
        foreach ($this->members as $member)
            $this->removeMember($member->getName());
    }



}