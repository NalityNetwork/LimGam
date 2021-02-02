<?php declare(strict_types = 1);

namespace LimGam\Game\Event;


use pocketmine\event\Event;


/**
 * @author  RomnSD
 * @package LimGam\Game\Event
 */
abstract class EventAction
{



    /** @var int */
    protected $priority;

    /** @var string */
    protected $game;



    /**
     * @param int $priority
     */
    public function __construct(int $priority = IGamEvent::PRIORITY_NORMAL)
    {
        $this->priority = $priority;
    }



    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }



    /**
     * @param string $game
     */
    public function setGame(string $game): void
    {
        $this->game = $game;
    }



    /**
     * @param Event $event
     * @param       $result
     * @return mixed
     */
    public abstract function process(Event $event, $result);



    /**
     * @return string
     */
    public abstract function getName(): string;



    /**
     * @return string
     */
    public abstract function getEvent(): string;



}