<?php declare(strict_types = 1);

namespace LimGam\Game\Event;


/**
 * @author  RomnSD
 * @package LimGam\Game\Event
 */
abstract class EventHeader extends EventAction
{



    /**
     * @return bool
     */
    public abstract function returnSession(): bool;



}