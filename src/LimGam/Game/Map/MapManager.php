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
    protected static $maps = [];



    /**
     * @param string ...$maps
     */
    public function load(string...$maps): void
    {
        foreach ($maps as $map)
        {

            if (!file_exists($map))
                continue;

            $config = (array) json_decode(file_get_contents($map), true);

            if (!file_exists(($config["File"] ?? "")))
                continue;

            $this->addMap(new Map($config));
        }

    }



    /**
     * @param Map $map
     */
    public function addMap(Map $map)
    {
        static::$maps[$map->getGame()][$map->getName()] = clone $map;
    }



    /**
     * @param string $game
     * @param string $name
     * @return Map|null
     */
    public function getMap(string $game, string $name): ?Map
    {
        if (isset(static::$maps[$game], static::$maps[$game][$name]))
            return clone static::$maps[$game][$name];

        return null;
    }



    /**
     * @param string $game
     * @param bool   $teamMap
     * @return array
     */
    public function getMaps(string $game, bool $teamMap = false): array
    {
        $maps   = [];
        $lookIn = (static::$maps[$game] ?? []);

        foreach ($lookIn as $map)
            if ($map->allowTeams() === $teamMap)
                $maps[] = clone $map;

        return $maps;
    }



    /**
     * @param string $game
     * @param int    $spawnsCount
     * @param bool   $teamMap
     * @return array
     */
    public function getMapsBySpawnsCount(string $game, int $spawnsCount, bool $teamMap = false): array
    {
        $maps = [];

        foreach ($this->getMaps($game, $teamMap) as $map)
            if (count($map->getSpawns()) === $spawnsCount)
                $maps[] = $map;

        return $maps;
    }



    /**
     * @return array
     */
    public function getGames(): array
    {
        return array_keys(static::$maps);
    }



    /**
     * TODO: test
     * @param Level  $level
     * @param string $to
     */
    public function compressMap(Level $level, string $to): void
    {
        if ($level->getProvider() instanceof SimpleMcRegion)
            throw new InvalidArgumentException("Cannot compress a level using SimpleMcRegion.");

        $zip  = new ZipArchive();
        $path = realpath(DATA . "worlds" . DIRECTORY_SEPARATOR . $level->getFolderName() . DIRECTORY_SEPARATOR);

        if ($zip->open($to, ZipArchive::CREATE))
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