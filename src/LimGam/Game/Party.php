<?php declare(strict_types = 1);

namespace LimGam\Game;


use InvalidArgumentException;


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

    /** @var bool */
    protected $MustPlayTogether;



    /**
     * @param string $owner
     * @param bool   $playTogether
     * @param array  $members
     */
    public function __construct(string $owner, bool $playTogether, string...$members)
    {
        if ($members === [])
            throw new InvalidArgumentException("Party cannot be empty.");

        $this->Owner            = $owner;
        $this->MustPlayTogether = $playTogether;
        $this->Members          = $members;
    }



    /**
     * @return string
     */
    public function GetOwner(): string
    {
        return $this->Owner;
    }



    /**
     * @return bool
     */
    public function PlayTogether(): bool
    {
        return $this->MustPlayTogether;
    }



    /**
     * @return array
     */
    public function GetMembers(): array
    {
        return $this->Members;
    }



}