<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game;


use Exception;
use LimGam\Game\Map\MapManager;
use LimGam\Game\Session\InGame;
use pocketmine\Player;


/**
 * @author  RomnSD
 * @package LimGam\Game
 */
class GameManager
{



    /** @var Game[] */
    protected $Games;

    /** @var InGame[] */
    protected $Sessions;

    /** @var MapManager */
    protected $MapManager;

    /** @var string */
    protected $SessionClass;



    /** Constructor */
    public function __construct()
    {
        $this->Games        = [];
        $this->Sessions     = [];
        $this->MapManager   = new MapManager();
        $this->SessionClass = InGame::class;
    }



    /**
     * @param string $class
     */
    public function SetSessionClass(string $class): void
    {
        if (!is_a($class, InGame::class, true) || count($this->Sessions))
            return;

        $this->SessionClass = $class;
    }



    /**
     * @param Player $player
     */
    public function AddSession(Player $player): void
    {
        if (isset($this->Sessions[$player->getName()]))
            return;

        $this->Sessions[$player->getName()] = new $this->SessionClass($player);
    }



    /**
     * @param string $name
     */
    public function RemoveSession(string $name): void
    {
        if (isset($this->Sessions[$name]))
        {
            $this->Sessions[$name]->Close();
            unset($this->Sessions[$name]);
        }
    }



    /**
     * @param string|Player $player
     * @return InGame|null
     */
    public function GetSession($player): ?InGame
    {
        if ($player instanceof Player)
        {
            if (!isset($this->Sessions[$player->getName()]))
                $this->Sessions[$player->getName()] = new InGame($player);

            $player = $player->getName();
        }

        return ($this->Sessions[(string) $player] ?? null);
    }



    /**
     * @return MapManager
     */
    public function GetMapManager(): MapManager
    {
        return $this->MapManager;
    }



    /**
     * @param string $game
     * @param Arena  ...$arenas
     * @throws Exception
     */
    public function AddGame(string $game, Arena...$arenas)
    {
        if (isset($this->Games[$game]))
            throw new Exception("Cannot add twice a game.");

        $this->Games[$game] = new Game($game);

        foreach ($arenas as $arena)
            $this->Games[$game]->AddArena($arena);
    }



    /**
     * @param string $name
     * @return Game|null
     */
    public function GetGame(string $name): ?Game
    {
        return ($this->Games[$name] ?? null);
    }



    /**
     * @param string $arenaID
     * @return Arena|null
     */
    public function GetArenaByID(string $arenaID): ?Arena
    {
        foreach ($this->Games as $game)
        {
            if (($found = $game->GetArena($arenaID)))
                return $found;
        }

        return null;
    }



    public function __destruct()
    {
        foreach ($this->Games as $game)
        {
            foreach ($game->GetArenas() as $arena)
                $arena->Close();
        }

        foreach (array_keys($this->Sessions) as $id)
            unset($this->Sessions[$id]); //sessions = []

        $this->MapManager = null;
    }



}