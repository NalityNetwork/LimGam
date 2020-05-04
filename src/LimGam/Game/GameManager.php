<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game;


use Exception;
use LimGam\Game\Event\Events\Player\PlayerJoinArena;
use LimGam\Game\Event\Events\Player\PlayerQuitArena;
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



    /** Constructor */
    public function __construct()
    {
        $this->Games      = [];
        $this->Sessions   = [];
        $this->MapManager = new MapManager();
    }



    /**
     * @param Player     $player
     * @param Arena      $arena
     * @param Party|null $party
     * @param int        $status
     * @return bool
     */
    public function AddSession(Player $player, Arena $arena, Party $party = null, int $status = InGame::STATUS_ALIVE): bool
    {
        if (isset($this->Sessions[$player->getName()]))
            return false;

        if (!$arena->IsJoinable() && $status !== InGame::STATUS_SPECTATING)
            return false;

        try
        {
            $this->Sessions[$player->getName()] = new InGame($player, $arena, $party, $status);
            (new PlayerJoinArena($this->Sessions[$player->getName()]))->call();
        }
        catch (Exception $e)
        {
            return false;
        }

        return true;
    }



    /**
     * @param string $name
     */
    public function RemoveSession(string $name): void
    {
        if (!isset($this->Sessions[$name]))
            return;

        $this->Sessions[$name]->Close();
        (new PlayerQuitArena($this->Sessions[$name]))->call();

        unset($this->Sessions[$name]);
    }



    /**
     * @param string $name
     * @return InGame|null
     */
    public function GetSession(string $name): ?InGame
    {
        return ($this->Sessions[$name] ?? null);
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

        if ($arenas !== [])
        {
            foreach ($arenas as $arena)
                $this->Games[$game]->AddArena($arena);
        }
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



}