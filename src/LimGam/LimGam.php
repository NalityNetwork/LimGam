<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam;


use LimGam\Game\GameManager;
use Performance\Performance;
use pocketmine\plugin\PluginBase;


/**
 * @author  RomnSD
 * @package LimGam
 */
class LimGam extends PluginBase
{



    /** @var LimGam */
    protected static $LimInstance;

    /** @var GameManager */
    protected static $GameManager;



    public function onLoad()
    {
        self::$LimInstance = $this;
        self::$GameManager = new GameManager();
    }



    /**
     * Return LimGam instance
     * @return LimGam
     */
    public static function GetInstance(): LimGam
    {
        return static::$LimInstance;
    }



    /**
     * @param GameManager $gameManager
     */
    public function SetGameManager(GameManager $gameManager): void
    {
        static::$GameManager = $gameManager;
        $this->getLogger()->debug("Default GameManager has been replaced.");
    }



    /**
     * Return GameManager instance
     * @return GameManager
     */
    public static function GetGameManager(): GameManager
    {
        return static::$GameManager;
    }



}