<?php declare(strict_types = 1);

namespace LimGam\Game\Session;


use pocketmine\Player;


/**
 * @author  RomnSD
 * @package LimSession
 */
abstract class LimSession
{



    /** @var string */
    protected $PlayerName;

    /** @var Player */
    protected $Player;



    /**
     * @param Player $player
     */
    public function __construct(Player $player)
    {
        $this->PlayerName = $player->getName();
        $this->Player     = $player;
    }



    /**
     * @return string
     */
    public function GetName(): string
    {
        return $this->PlayerName;
    }



    /**
     * @return Player
     */
    public function GetPlayer(): Player
    {
        return $this->Player;
    }



}