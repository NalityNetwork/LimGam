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
    protected $file;

    /** @var string */
    protected $name;

    /** @var string */
    protected $game;

    /** @var bool */
    protected $allowTeams;

    /** @var array */
    protected $builders;

    /** @var Position[] */
    protected $spawns;

    /** @var Vector3 */
    protected $temporalVector;

    /** @var int */
    protected $mapID;

    /** @var Location|null */
    protected $lobbyLocation;

    /** @var Location|null */
    protected $spectatorLocation;

    /** @var Level|null */
    protected $levelObject;

    /** @var int */
    protected static $mapCounter = 0;

    /** @var array Default config que onda
     * template
     */
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
        $this->file              = realpath($config["File"]);
        $this->name              = basename($this->file, ".zip");
        $this->game              = $config["Game"];
        $this->allowTeams        = $config["AllowTeams"];
        $this->builders          = $config["Builders"];
        $this->spawns            = $config["Spawns"];
        $this->lobbyLocation     = new Location();
        $this->spectatorLocation = new Location();
        $this->temporalVector    = new Vector3(0, 100, 0);
        $this->mapID             = static::$mapCounter++;

        foreach ($this->spawns as &$vector)
            $vector = new Position((float) ($vector[0] ?? 0), (float) ($vector[1] ?? 0), (float) ($vector[2] ?? 0));
    }



    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        if ($this->levelObject)
            return;

        $this->name = $name;
    }



    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }



    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }



    /**
     * @return string
     */
    public function getGame(): string
    {
        return $this->game;
    }



    /**
     * @return bool
     */
    public function allowTeams(): bool
    {
        return $this->allowTeams;
    }



    /**
     * @return array
     */
    public function getBuilders(): array
    {
        return $this->builders;
    }



    /**
     * @return Position[]
     */
    public function getSpawns(): array
    {
        return $this->spawns;
    }



    /**
     * @param Location $lobby
     */
    public function setLobby(Location $lobby): void
    {
        $this->lobbyLocation = $lobby;
    }



    /**
     * @return Location
     */
    public function getLobby(): Location
    {
        return $this->lobbyLocation;
    }



    /**
     * @param Location $spectatorSpawn
     */
    public function setSpectatorSpawn(Location $spectatorSpawn): void
    {
        $this->spectatorLocation = $spectatorSpawn;
    }



    /**
     * @return Location
     */
    public function getSpectatorSpawn(): Location
    {
        return $this->spectatorLocation;
    }



    /**
     * @return Vector3
     */
    public function getTemporalVector(): Vector3
    {
        return $this->temporalVector;
    }



    /**
     * @return int
     */
    public function getID(): int
    {
        return $this->mapID;
    }



    /**
     * @param Level $level
     * @param bool  $closeOld
     */
    public function setLevelObject(?Level $level = null, bool $closeOld = true): void
    {
        if ($this->levelObject && $closeOld)
            LimGam::GetInstance()->getServer()->unloadLevel($this->levelObject, true);

        $this->levelObject = $level;

        foreach ($this->spawns as &$spawn)
            $spawn->setLevel($this->levelObject);
    }



    /**
     * @return Level|null
     */
    public function getLevelObject(): ?Level
    {
        return $this->levelObject;
    }


    //TODO: Method for extracting zip files



    /**
     * @param string|null $customName
     * @return Level
     * @throws Exception
     */
    public function toLevelObject(string $customName = null): ?Level
    {
        $level = SimpleLevel::GetLevel($this, $customName ? $customName : $this->getName());

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

        foreach ($this->spawns as $spawn)
            $spawns[] = [$spawn->x, $spawn->y, $spawn->z];

        return [
            "File"       => $this->file,
            "Game"       => $this->game,
            "AllowTeams" => $this->allowTeams,
            "Builders"   => $this->builders,
            "Spawns"     => $spawns
        ];
    }

    public function decompress(string $folderName): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($this->getFile())) {
            $zip->extractTo(LimGam::getInstance()->getServer()->getDataPath() . "worlds/" . $folderName);
        }
        $zip->close();
    }



    public function __destruct()
    {
        if ($this->levelObject && !($this->levelObject->isClosed()))
            LimGam::GetInstance()->getServer()->unloadLevel($this->levelObject);
    }



}