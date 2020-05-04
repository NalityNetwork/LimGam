<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game\Event\Events\Entity;


use LimGam\Game\Event\IGamEventListener;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityEvent;


/**
 * @author  RomnSD
 * @package LimGam\Game\Event\Events\Entity
 */
class FakeDeathEvent extends EntityEvent implements IGamEventListener
{



    /** @var array */
    protected $Drops;



    /**
     * @param Entity $entity
     * @param array  $drops
     */
    public function __construct(Entity $entity, array $drops)
    {
        $this->entity = $entity;
        $this->Drops  = $drops;
    }



    /**
     * @return array
     */
    public function GetDrops(): array
    {
        return $this->Drops;
    }



}