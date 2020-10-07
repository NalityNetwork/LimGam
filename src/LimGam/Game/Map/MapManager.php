<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game\Map;


use InvalidArgumentException;
use LimGam\Level\Provider\SimpleMcRegion;
use LimGam\LimGam;
use RecursiveDirectoryIterator;
use ZipArchive;
use pocketmine\level\Level;
use const pocketmine\DATA;


/**
 * @author  RomnSD
 * @package LimGam\Game\Map
 */
class MapManager
{



    /** @var Map[] */
    protected static $Maps = [];



    /**
     * @param string ...$maps
     */
    public function Load(string...$maps): void
    {
        foreach ($maps as $map)
        {

            if (!file_exists($map))
                continue;

            $config = (array) json_decode(file_get_contents($map), true);

            if (!file_exists(($config["File"] ?? "")))
                continue;

            $this->AddMap(new Map($config));
        }

    }



    /**
     * @param Map $map
     */
    public function AddMap(Map $map)
    {
        static::$Maps[$map->GetGame()][$map->GetName()] = clone $map;
    }



    /**
     * @param string $game
     * @param string $name
     * @return Map|null
     */
    public function GetMap(string $game, string $name): ?Map
    {
        if (isset(static::$Maps[$game], static::$Maps[$game][$name]))
            return clone static::$Maps[$game][$name];

        return null;
    }



    /**
     * @param string $game
     * @param bool   $teamMap
     * @return array
     */
    public function GetMaps(string $game, bool $teamMap = false): array
    {
        $maps   = [];
        $lookIn = (static::$Maps[$game] ?? []);

        foreach ($lookIn as $map)
            if ($map->AllowTeams() === $teamMap)
                $maps[] = clone $map;

        return $maps;
    }



    /**
     * @param string $game
     * @param int    $spawnsCount
     * @param bool   $teamMap
     * @return array
     */
    public function GetMapsBySpawnsCount(string $game, int $spawnsCount, bool $teamMap = false): array
    {
        $maps = [];

        foreach ($this->GetMaps($game, $teamMap) as $map)
            if (count($map->GetSpawns()) === $spawnsCount)
                $maps[] = $map;

        return $maps;
    }



    /**
     * @return array
     */
    public function GetGames(): array
    {
        return array_keys(static::$Maps);
    }



    /**
     * TODO: test
     * @param Level  $level
     * @param string $to
     */
    public function CompressMap(Level $level, string $to): void
    {
        if ($level->getProvider() instanceof SimpleMcRegion)
            throw new InvalidArgumentException("Cannot compress a level using SimpleMcRegion.");

        $zip  = new ZipArchive();
        $path = realpath(DATA . "worlds" . DIRECTORY_SEPARATOR . $level->getFolderName() . DIRECTORY_SEPARATOR);

        if ($zip->open(sprintf("%s%s.zip", $to, $level->getFolderName()), ZipArchive::CREATE))
        {
            $files = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
            foreach (new \RecursiveIteratorIterator($files, \RecursiveIteratorIterator::CHILD_FIRST) as $file)
            {
                $file = $file->getRealPath();
                if (is_file($file))
                    $zip->addFile($file, str_replace("\\", DIRECTORY_SEPARATOR, ltrim(substr($file, strlen($path)), "/\\")));
            }
        }

        $zip->close();
    }



}