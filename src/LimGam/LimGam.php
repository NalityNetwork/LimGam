<?php
declare(strict_types = 1);

namespace LimGam;


use LimGam\Game\GameManager;
use pocketmine\plugin\PluginBase;


/**
 * @author  RomnSD
 * @package LimGam
 */
class LimGam extends PluginBase
{



    /** @var LimGam */
    protected static $limInstance;

    /** @var GameManager */
    protected static $gameManager;



    public function onLoad()
    {
        self::$limInstance = $this;
        self::$gameManager = new GameManager();
    }



    /**
     * Return LimGam instance
     * @return LimGam
     */
    public static function getInstance(): LimGam
    {
        return static::$limInstance;
    }



    /**
     * @param GameManager $gameManager
     */
    public function setGameManager(GameManager $gameManager): void
    {
        static::$gameManager = $gameManager;
        $this->getLogger()->debug("Default GameManager has been replaced.");
    }



    /**
     * Return GameManager instance
     * @return GameManager
     */
    public static function getGameManager(): GameManager
    {
        return static::$gameManager;
    }



}