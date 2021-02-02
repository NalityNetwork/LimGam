<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Level\Provider;


use Exception;
use pocketmine\level\format\io\region\RegionGarbageMap;
use pocketmine\level\format\io\region\RegionLoader;


/**
 * @author  RomnSD
 * @package LimGam\Level\Provider
 */
class SimpleRegionLoader extends RegionLoader
{




    /**
     * @param int $regionX
     * @param int $regionZ
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(int $regionX, int $regionZ)
    {
        $this->x            = $regionX;
        $this->z            = $regionZ;
        $this->garbageTable = new RegionGarbageMap([]);
    }



    /**
     * @return bool|void
     * @deprecated
     */
    public function open()
    {
        return trigger_error("Method 'open' is not longer working, use 'OpenRegion' instead.", E_USER_WARNING);
    }




    /**
     * @param string|null $data
     * @throws Exception
     */
    public function openRegion(string $data = null)
    {
        $this->filePointer = tmpfile();

        stream_set_read_buffer($this->filePointer, 1024 * 16);
        stream_set_write_buffer($this->filePointer, 1024 * 16);

        if ($data)
        {
            if ((strlen($data) % 4096) !== 0)
                throw new Exception("Region data is corrupted.");

            fwrite($this->filePointer, $data);
            fseek($this->filePointer, 0);

            $this->loadLocationTable();

            return;
        }

        $this->createBlank();

        $this->lastUsed = time();
    }



    /**
     * @param bool $writeHeader
     */
    public function close(bool $writeHeader = true)
    {
        return;
    }



    public function __destruct()
    {
        if ($this->filePointer)
            @fclose($this->filePointer);
    }



}