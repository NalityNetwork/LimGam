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
    protected $Priority;

    /** @var string */
    protected $Game;



    /**
     * @param int $priority
     */
    public function __construct(int $priority = IGamEvent::PRIORITY_NORMAL)
    {
        $this->Priority = $priority;
    }



    /**
     * @return int
     */
    public function GetPriority(): int
    {
        return $this->Priority;
    }



    /**
     * @param string $game
     */
    public function SetGame(string $game): void
    {
        $this->Game = $game;
    }



    /**
     * @param Event $event
     * @param       $result
     * @return mixed
     */
    public abstract function Process(Event $event, $result);



    /**
     * @return string
     */
    public abstract function GetName(): string;



    /**
     * @return string
     */
    public abstract function GetEvent(): string;



}