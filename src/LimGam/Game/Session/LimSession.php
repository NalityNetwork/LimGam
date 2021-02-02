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
    protected $playerName;

    /** @var Player */
    protected $player;



    /**
     * @param Player $player
     */
    public function __construct(Player $player)
    {
        $this->playerName = $player->getName();
        $this->player     = $player;
    }



    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->playerName;
    }



    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }



}