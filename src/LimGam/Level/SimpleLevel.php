<?php declare(strict_types = 1);

namespace LimGam\Level;


use Closure;
use Exception;
use Throwable;
use LimGam\LimGam;
use LimGam\Level\Provider\SimpleMcRegion;
use LimGam\Level\Provider\SimpleAnvil;
use LimGam\Game\Map\Map;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\level\format\io\BaseLevelProvider;
use pocketmine\level\Level;
use pocketmine\Server;


/**
 * @author  RomnSD
 * @package LimGam\Level
 */
class SimpleLevel
{



    /** @var string[] */
    protected static $Providers = [
        SimpleMcRegion::REGION_FILE_EXTENSION => SimpleMcRegion::class,
        SimpleAnvil::REGION_FILE_EXTENSION    => SimpleAnvil::class
    ];


    public static function AddLevelProvider(string $provider, string $extension): void
    {
        if (!is_a($provider, BaseLevelProvider::class, true))
            throw new Exception($provider . " must be part of " . BaseLevelProvider::class);

        self::$Providers[$extension] = $provider;
    }



    /**
     * @param Map               $map
     * @param string            $name Level name
     * @param BaseLevelProvider $provider
     * @return Level|null
     * @noinspection PhpUnused
     */
    public static function GetLevel(Map $map, string $name, BaseLevelProvider $provider = null): ?Level
    {
        try
        {
            /** @var SimpleMcRegion|BaseLevelProvider $provider */
            $provider = $provider ?? self::GetProvider($map->GetFile());

            if (!$provider)
                throw new Exception("Cannot find a valid level provider for " . $map->GetFile());

            $level = new Level(Server::getInstance(), $name, ($provider instanceof SimpleMcRegion) ? $provider->Name($name) : $provider);

            //Server::getInstance()->getLevels()[$level->getId()] = $level;
            //(new LevelLoadEvent($level))->call();

            (Closure::bind(function (Server $server) use ($level) {
                /** @noinspection Annotator */
                $server->levels[$level->getId()] = $level;
                (new LevelLoadEvent($level))->call();

            }, null, Server::class))(Server::getInstance());

            return $level;

        }
        catch (Throwable $e)
        {
            LimGam::GetInstance()->getLogger()->logException($e);
        }

        return null;
    }



    /**
     * @param string $file
     * @return BaseLevelProvider|null
     */
    public static function GetProvider(string $file): ?BaseLevelProvider
    {

        $zip = zip_open($file);
        $ext = null;

        if (!$zip)
            return null;

        while ($entry = zip_read($zip))
        {
            $entry_name = zip_entry_name($entry);
            zip_entry_close($entry);

            if (basename(dirname($entry_name)) !== "region")
                continue;

            $provider = (string) substr($entry_name, strripos($entry_name, ".") + 1);

            if ($ext === null)
                $ext = $provider;

            if ($ext !== $provider)
                return null;
        }

        zip_close($zip);

        if (!isset(static::$Providers[$ext]))
            return null;

        return new static::$Providers[$ext]($file);
    }



}