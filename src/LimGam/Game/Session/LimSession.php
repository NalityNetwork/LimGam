<?php declare(strict_types = 1);

namespace LimGam\Game\Session;


use pocketmine\Player;


/**
 * @author  RomnSD
 * @package LimSession
 */
abstract class LimSession
{



    /** @var Player */
    protected $Player;



    /**
     * @param Player $player
     */
    public function __construct(Player $player)
    {
        $this->Player = $player;
    }



    /**
     * @return Player
     */
    public function GetPlayer(): Player
    {
        return $this->Player;
    }



}