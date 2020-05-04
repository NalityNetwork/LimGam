<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game\Map;


use Exception;
use JsonSerializable;
use LimGam\Level\SimpleLevel;
use LimGam\LimGam;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\math\Vector3;


/**
 * @author  RomnSD
 * @package LimGam\Game\Map
 */
class Map implements JsonSerializable
{



    /** @var string */
    protected $File;

    /** @var string */
    protected $Name;

    /** @var string */
    protected $Game;

    /** @var bool */
    protected $AllowTeams;

    /** @var array */
    protected $Builders;

    /** @var Position[] */
    protected $Spawns;

    /** @var Vector3 */
    protected $TemporalVector;

    /** @var int */
    protected $MapID;

    /** @var Location|null */
    protected $LobbyLocation;

    /** @var Location|null */
    protected $SpectatorLocation;

    /** @var Level|null */
    protected $LevelObject;

    /** @var int */
    protected static $MapCounter = 0;

    /** @var array */
    public const CONFIG = [
        "File"       => "",
        "Game"       => "",
        "AllowTeams" => false,
        "Builders"   => [],
        "Spawns"     => [],
    ];



    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->File              = realpath($config["File"]);
        $this->Name              = basename($this->File, ".zip");
        $this->Game              = $config["Game"];
        $this->AllowTeams        = $config["AllowTeams"];
        $this->Builders          = $config["Builders"];
        $this->Spawns            = $config["Spawns"];
        $this->LobbyLocation     = new Location();
        $this->SpectatorLocation = new Location();
        $this->TemporalVector    = new Vector3(0, 100, 0);
        $this->MapID             = static::$MapCounter++;

        foreach ($this->Spawns as &$vector)
            $vector = new Position((float) ($vector[0] ?? 0), (float) ($vector[1] ?? 0), (float) ($vector[2] ?? 0));
    }



    /**
     * @param string $name
     */
    public function SetName(string $name): void
    {
        if ($this->LevelObject)
            return;

        $this->Name = $name;
    }



    /**
     * @return string
     */
    public function GetName(): string
    {
        return $this->Name;
    }



    /**
     * @return string
     */
    public function GetFile(): string
    {
        return $this->File;
    }



    /**
     * @return string
     */
    public function GetGame(): string
    {
        return $this->Game;
    }



    /**
     * @return bool
     */
    public function AllowTeams(): bool
    {
        return $this->AllowTeams;
    }



    /**
     * @return array
     */
    public function GetBuilders(): array
    {
        return $this->Builders;
    }



    /**
     * @return Vector3[]
     */
    public function GetSpawns(): array
    {
        return $this->Spawns;
    }



    /**
     * @param Location $lobby
     */
    public function SetLobby(Location $lobby): void
    {
        $this->LobbyLocation = $lobby;
    }



    /**
     * @return Location
     */
    public function GetLobby(): Location
    {
        return $this->LobbyLocation;
    }



    /**
     * @param Location $spectatorSpawn
     */
    public function SetSpectatorSpawn(Location $spectatorSpawn): void
    {
        $this->SpectatorLocation = $spectatorSpawn;
    }



    /**
     * @return Location
     */
    public function GetSpectatorSpawn(): Location
    {
        return $this->SpectatorLocation;
    }



    /**
     * @return Vector3
     */
    public function GetTemporalVector(): Vector3
    {
        return $this->TemporalVector;
    }



    /**
     * @return int
     */
    public function GetID(): int
    {
        return $this->MapID;
    }



    /**
     * @param Level $level
     * @param bool  $closeOld
     */
    public function SetLevelObject(Level $level = null, bool $closeOld = true): void
    {
        if ($this->LevelObject && $closeOld)
            LimGam::GetInstance()->getServer()->unloadLevel($this->LevelObject, true);

        $this->LevelObject = $level;

        foreach ($this->Spawns as $spawn)
            $spawn->setLevel($this->LevelObject);
    }



    /**
     * @return Level|null
     */
    public function GetLevelObject(): ?Level
    {
        return $this->LevelObject;
    }


    //TODO: Method for extracting zip files



    /**
     * @param string|null $customName
     * @return Level
     * @throws Exception
     */
    public function ToLevelObject(string $customName = null): ?Level
    {
        $level = SimpleLevel::GetLevel($this, $customName ? $customName : $this->GetName());

        if (!$level)
            throw new Exception("Occurred an error while creating the level.");

        return $level;
    }



    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        $spawns = [];

        foreach ($this->Spawns as $spawn)
            $spawns[] = [$spawn->getX(), $spawn->getY(), $spawn->getZ()];

        return [
            "File"     => $this->File,
            "Game"     => $this->Game,
            "Builders" => $this->Builders,
            "Spawns"   => $spawns
        ];
    }



    public function __destruct()
    {
        if ($this->LevelObject && !($this->LevelObject->isClosed()))
            LimGam::GetInstance()->getServer()->unloadLevel($this->LevelObject);
    }



}