<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Level\Provider;


use pocketmine\level\format\Chunk;
use pocketmine\level\format\io\ChunkUtils;
use pocketmine\level\format\io\exception\CorruptedChunkException;
use pocketmine\level\format\SubChunk;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntArrayTag;
use pocketmine\nbt\tag\ListTag;


/**
 * @author  RomnSD | PocketMine Team
 * @package LimGam\Level\Provider
 */
class SimpleAnvil extends SimpleMcRegion
{



    /** @var string */
    public const REGION_FILE_EXTENSION = "mca";



    /**
     * @param Chunk $chunk
     * @return string
     */
    protected function nbtSerialize(Chunk $chunk): string
    {
        $nbt = new CompoundTag("Level", []);
        $nbt->setInt("xPos", $chunk->getX());
        $nbt->setInt("zPos", $chunk->getZ());

        $nbt->setByte("V", 1);
        $nbt->setLong("LastUpdate", 0);
        $nbt->setLong("InhabitedTime", 0);
        $nbt->setByte("TerrainPopulated", $chunk->isPopulated() ? 1 : 0);
        $nbt->setByte("LightPopulated", $chunk->isLightPopulated() ? 1 : 0);

        $subChunks = [];

        foreach ($chunk->getSubChunks() as $y => $subChunk)
        {
            if (!($subChunk instanceof SubChunk) or $subChunk->isEmpty())
                continue;

            $tag = $this->serializeSubChunk($subChunk);
            $tag->setByte("Y", $y);

            $subChunks[] = $tag;
        }

        $nbt->setTag(new ListTag("Sections", $subChunks, NBT::TAG_Compound));
        $nbt->setByteArray("Biomes", $chunk->getBiomeIdArray());
        $nbt->setIntArray("HeightMap", $chunk->getHeightMapArray());

        $entities = [];

        foreach ($chunk->getSavableEntities() as $entity)
        {
            $entity->saveNBT();
            $entities[] = $entity->namedtag;
        }

        $nbt->setTag(new ListTag("Entities", $entities, NBT::TAG_Compound));

        $tiles = [];

        foreach ($chunk->getTiles() as $tile)
            $tiles[] = $tile->saveNBT();

        $nbt->setTag(new ListTag("TileEntities", $tiles, NBT::TAG_Compound));

        $writer = new BigEndianNBTStream();
        return $writer->writeCompressed(new CompoundTag("", [$nbt]), ZLIB_ENCODING_DEFLATE, SimpleRegionLoader::$COMPRESSION_LEVEL);
    }



    /**
     * @param SubChunk $subChunk
     * @return CompoundTag
     */
    protected function serializeSubChunk(SubChunk $subChunk): CompoundTag
    {
        return new CompoundTag("", [
            new ByteArrayTag("Blocks", ChunkUtils::reorderByteArray($subChunk->getBlockIdArray())), //Generic in-memory chunks are currently always XZY
            new ByteArrayTag("Data", ChunkUtils::reorderNibbleArray($subChunk->getBlockDataArray())),
            new ByteArrayTag("SkyLight", ChunkUtils::reorderNibbleArray($subChunk->getBlockSkyLightArray(), "\xff")),
            new ByteArrayTag("BlockLight", ChunkUtils::reorderNibbleArray($subChunk->getBlockLightArray()))
        ]);
    }



    /**
     * @param string $data
     * @return Chunk
     */
    protected function nbtDeserialize(string $data): Chunk
    {
        $data = @zlib_decode($data);

        if ($data === false)
            throw new CorruptedChunkException("Failed to decompress chunk data");

        $nbt   = new BigEndianNBTStream();
        $chunk = $nbt->read($data);

        if (!($chunk instanceof CompoundTag) or !$chunk->hasTag("Level"))
            throw new CorruptedChunkException("'Level' key is missing from chunk NBT");

        $chunk = $chunk->getCompoundTag("Level");

        $subChunks    = [];
        $subChunksTag = $chunk->getListTag("Sections") ?? [];

        foreach ($subChunksTag as $subChunk)
        {
            if ($subChunk instanceof CompoundTag)
                $subChunks[$subChunk->getByte("Y")] = $this->deserializeSubChunk($subChunk);
        }

        $biomeIds = ($chunk->hasTag("BiomeColors", IntArrayTag::class)) ? ChunkUtils::convertBiomeColors($chunk->getIntArray("BiomeColors")) : $chunk->getByteArray("Biomes", "", true);

        $result = new Chunk(
            $chunk->getInt("xPos"),
            $chunk->getInt("zPos"),
            $subChunks,
            $chunk->hasTag("Entities", ListTag::class) ? self::getCompoundList("Entities", $chunk->getListTag("Entities")) : [],
            $chunk->hasTag("TileEntities", ListTag::class) ? self::getCompoundList("TileEntities", $chunk->getListTag("TileEntities")) : [],
            $biomeIds,
            $chunk->getIntArray("HeightMap", [])
        );

        $result->setLightPopulated($chunk->getByte("LightPopulated", 0) !== 0);
        $result->setPopulated($chunk->getByte("TerrainPopulated", 0) !== 0);
        $result->setGenerated();

        return $result;
    }



    /**
     * @param CompoundTag $subChunk
     * @return SubChunk
     */
    protected function deserializeSubChunk(CompoundTag $subChunk): SubChunk
    {
        return new SubChunk(
            ChunkUtils::reorderByteArray($subChunk->getByteArray("Blocks")),
            ChunkUtils::reorderNibbleArray($subChunk->getByteArray("Data")),
            ChunkUtils::reorderNibbleArray($subChunk->getByteArray("SkyLight"), "\xff"),
            ChunkUtils::reorderNibbleArray($subChunk->getByteArray("BlockLight"))
        );
    }



    /**
     * @return string
     */
    public static function getProviderName(): string
    {
        return "SimpleAnvil";
    }



    /**
     * @return int
     */
    public static function getPcWorldFormatVersion(): int
    {
        return 19133;
    }



    /**
     * @return int
     */
    public function getWorldHeight(): int
    {
        return 256;
    }



}