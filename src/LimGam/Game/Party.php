<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game;


use InvalidArgumentException;
use LimGam\LimGam;


/**
 * @author  RomnSD
 * @package LimGam\Game
 */
class Party
{



    /** @var string */
    protected $owner;

    /** @var array */
    protected $members;



    /**
     * @param string $owner
     * @param array  $members
     */
    public function __construct(string $owner, array $members)
    {
        $this->owner   = $owner;
        $this->members = (function(string ...$check){$list = []; foreach ($check as $index) $list[$index] = $index; return $list;})(...$members);
    }



    /**
     * @return string
     */
    public function getOwner(): string
    {
        return $this->owner;
    }

    public function setOwner(string $member): void
    {
        if ($this->owner === $member || !isset($this->members[$member]))
            throw new InvalidArgumentException();

        unset($this->members[$member]);
        $this->members[$this->owner] = $this->owner;
        $this->owner = $member;
    }



    public function addPlayer(string $player): void
    {
        $this->members[$player] = $player;
    }



    /**
     * @param string $member
     */
    public function removeMember(string $member): void
    {
        if (!isset($this->members[$member]))
            return;

        $session = LimGam::GetGameManager()->getSession($member);

        if ($session)
            $session->setParty(null);

        unset($this->members[$member]);
    }



    /**
     * @return array
     */
    public function getMembers(): array
    {
        return $this->members;
    }



    /**
     * Disband the party.
     */
    public function disband(): void
    {
        $members  = $this->members;
        $members += [$this->owner];

        foreach ($members as $member)
        {
            if ($session = LimGam::GetGameManager()->getSession($member))
                $session->setParty(null);
        }
    }



}