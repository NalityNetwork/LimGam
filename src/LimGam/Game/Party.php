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
    protected $Owner;

    /** @var array */
    protected $Members;



    /**
     * @param string $owner
     * @param array  $members
     */
    public function __construct(string $owner, array $members)
    {
        $this->Owner   = $owner;
        $this->Members = (function(string ...$check){$list = []; foreach ($check as $index) $list[$index] = $index; return $list;})(...$members);
    }



    /**
     * @return string
     */
    public function GetOwner(): string
    {
        return $this->Owner;
    }

    public function SetOwner(string $member): void
    {
        if ($this->Owner === $member || !isset($this->Members[$member]))
            throw new InvalidArgumentException();

        unset($this->Members[$member]);
        $this->Members[$this->Owner] = $this->Owner;
        $this->Owner = $member;
    }



    public function AddPlayer(string $player): void
    {
        $this->Members[$player] = $player;
    }



    /**
     * @param string $member
     */
    public function RemoveMember(string $member): void
    {
        if (!isset($this->Members[$member]))
            return;

        $session = LimGam::GetGameManager()->GetSession($member);

        if ($session)
            $session->SetParty(null);

        unset($this->Members[$member]);
    }



    /**
     * @return array
     */
    public function GetMembers(): array
    {
        return $this->Members;
    }



    /**
     * Disband the party.
     */
    public function Disband(): void
    {
        $members  = $this->Members;
        $members += [$this->Owner];

        foreach ($members as $member)
        {
            if ($session = LimGam::GetGameManager()->GetSession($member))
                $session->SetParty(null);
        }
    }



}