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
    protected $games;

    /** @var InGame[] */
    protected $sessions;

    /** @var MapManager */
    protected $mapManager;

    /** @var string */
    protected $sessionClass;



    /** Constructor */
    public function __construct()
    {
        $this->games        = [];
        $this->sessions     = [];
        $this->mapManager   = new MapManager();
        $this->sessionClass = InGame::class;
    }



    /**
     * @param string $class
     */
    public function setSessionClass(string $class): void
    {
        if (!is_a($class, InGame::class, true) || count($this->sessions))
            return;

        $this->sessionClass = $class;
    }



    /**
     * @param Player $player
     */
    public function addSession(Player $player): void
    {
        if (isset($this->sessions[$player->getName()]))
            return;

        $this->sessions[$player->getName()] = new $this->sessionClass($player);
    }



    /**
     * @param string $name
     */
    public function removeSession(string $name): void
    {
        if (isset($this->sessions[$name]))
        {
            $this->sessions[$name]->close();
            unset($this->sessions[$name]);
        }
    }



    /**
     * @param string|Player $player
     * @return InGame|null
     */
    public function getSession($player): ?InGame
    {
        if ($player instanceof Player)
        {
            if (!isset($this->sessions[$player->getName()]))
                $this->sessions[$player->getName()] = new InGame($player);

            $player = $player->getName();
        }

        return ($this->sessions[(string) $player] ?? null);
    }



    /**
     * @return MapManager
     */
    public function getMapManager(): MapManager
    {
        return $this->mapManager;
    }



    /**
     * @param string $game
     * @param Arena  ...$arenas
     * @throws Exception
     */
    public function addGame(string $game, Arena...$arenas)
    {
        if (isset($this->games[$game]))
            throw new Exception("Cannot add twice a game.");

        $this->games[$game] = new Game($game);

        foreach ($arenas as $arena)
            $this->games[$game]->addArena($arena);
    }



    /**
     * @param string $name
     * @return Game|null
     */
    public function getGame(string $name): ?Game
    {
        return ($this->games[$name] ?? null);
    }



    /**
     * @param string $arenaID
     * @return Arena|null
     */
    public function getArenaByID(string $arenaID): ?Arena
    {
        foreach ($this->games as $game)
        {
            if (($found = $game->getArena($arenaID)))
                return $found;
        }

        return null;
    }



    public function __destruct()
    {
        foreach ($this->games as $game)
        {
            foreach ($game->getArenas() as $arena)
                $arena->close();
        }

        foreach (array_keys($this->sessions) as $id)
            unset($this->sessions[$id]); //sessions = []

        $this->mapManager = null;
    }



}