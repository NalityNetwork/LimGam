<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Level\Provider;


use ZipArchive;
use Exception;
use pocketmine\level\Level;
use pocketmine\level\format\io\region\McRegion;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;


/**
 * @author  RomnSD
 * @package LimGam\Level\Provider
 */
class SimpleMcRegion extends McRegion
{



    /** @var null|string */
    protected $name;

    /** @var resource */
    protected $ZipResource;

    /** @var array */
    protected $zipEntries = [];



    /**
     * @param string $path
     * @throws Exception
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(string $path)
    {
        if (!file_exists($path))
            throw new Exception("The given zip file does not exist.");

        $zip = new ZipArchive();

        if (!$zip->open($path))
            throw new Exception("Cannot open the zip file, it may be broken.");

        $zip->close();

        $this->path        = $path;
        $this->zipResource = zip_open($path);

        while(is_resource($entry = zip_read($this->zipResource))) {

            if (preg_match('#region/#', ($name = zip_entry_name($entry))) !== 1) {
                if (basename($name) !== 'level.dat') {
                    zip_entry_close($entry);
                    continue;
                }
                $this->zipEntries['level'] = $entry;
                continue;
            }
            [$r, $x, $z, $ext] = explode('.', substr($name, 8)); //TODO: mejorar
            $this->zipEntries[$x . '.' . $z] = $entry;
        }

        if (!isset($this->zipEntries['level']))
            throw new Exception("level.dat was not found in " . $this->path);

        $this->loadLevelData();
        $this->fixLevelData();
    }



    /**
     * @param string $name
     * @return $this
     */
    public function name(string $name): self
    {
        if ($this->name === null)
            $this->name = $name; //setString

        return $this;
    }



    /**
     * @return string
     */
    public function getName(): string
    {
        return ($this->name ?? $this->levelData->getString("name"));
    }



    /**
     * @throws Exception
     */
    protected function loadLevelData(): void
    {
        $entry = $this->zipEntries['level'] ?? null;
        $nbt   = new BigEndianNBTStream();
        $data  = $nbt->readCompressed(zip_entry_read($entry, zip_entry_filesize($entry)));

        if (!($data instanceof CompoundTag) || !$data->hasTag("Data", CompoundTag::class))
            throw new Exception("Invalid level.dat");

        $this->levelData = $data->getCompoundTag("Data");

        zip_entry_close($entry);
    }




    /**
     * @param int $regionX
     * @param int $regionZ
     * @throws Exception
     */
    protected function loadRegion(int $regionX, int $regionZ)
    {
        if (isset($this->regions[$index = Level::chunkHash($regionX, $regionZ)]))
            return;

        $region = new SimpleRegionLoader($regionX, $regionZ);
        $valid  = false;

        try
        {
            $entry = $this->zipEntries[$regionX . '.' . $regionZ] ?? null;

            if ($entry) {
                $valid = true;
                $region->openRegion(zip_entry_read($entry, zip_entry_filesize($entry)));
            }

            if (!$valid)
                throw new \Exception("Region($regionX, $regionZ) wasn't found, creating a new empty region...");

        }
        catch (Exception $e)
        {
            $region = new SimpleRegionLoader($regionX, $regionZ);
            $region->openRegion();
        }

        $this->regions[$index] = $region;
    }



    /**
     * @param string $path
     * @return bool
     */
    public static function isValid(string $path): bool
    {
        return false;
    }



    /**
     * @return string
     */
    public static function getProviderName(): string
    {
        return "SimpleMcRegion";
    }



    /**
     * @return bool
     */
    public function saveLevelData(): bool
    {
        return false;
    }



    /**
     * @param string $path
     * @param string $name
     * @param int    $seed
     * @param string $generator
     * @param array  $options
     */
    public static function generate(string $path, string $name, int $seed, string $generator, array $options = []): void
    {
        return;
    }

    public function __destruct()
    {
        foreach ($this->zipEntries as $entry) {
            @zip_entry_close($entry);
        }
        @zip_close($this->zipResource);
    }



}