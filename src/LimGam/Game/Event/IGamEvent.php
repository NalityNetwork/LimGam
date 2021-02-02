<?php /** @noinspection PhpUnused */
declare(strict_types = 1);

namespace LimGam\Game\Event;


use InvalidArgumentException;
use LimGam\Game\Session\InGame;
use LimGam\LimGam;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\plugin\EventExecutor;


/**
 * @author  RomnSD
 * @package LimGam\Game\Event
 */
class IGamEvent implements EventExecutor, IGamEventListener
{



    /** @var string */
    protected $game;

    /** @var string */
    protected $event;

    /** @var EventHeader */
    protected $header;

    /** @var EventAction[] */
    protected $actions;


    public const PRIORITY_LOW    = 2;
    public const PRIORITY_NORMAL = 1;
    public const PRIORITY_HIGH   = 0;

    protected static $pRIORITIES = [
        IGamEvent::PRIORITY_LOW    => true,
        IGamEvent::PRIORITY_NORMAL => true,
        IGamEvent::PRIORITY_HIGH   => true
    ];



    /**
     * @param string $game
     * @param string $event
     * @param int    $priority
     */
    public function __construct(string $game, string $event, int $priority = EventPriority::NORMAL)
    {
        $this->game    = $game;
        $this->event   = $event;
        $this->actions = [];

        LimGam::GetInstance()->getServer()->getPluginManager()->registerEvent($event, $this, $priority, $this, LimGam::GetInstance());
    }



    /**
     * @return string
     */
    public function getGame(): string
    {
        return $this->game;
    }



    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }



    /**
     * @param EventHeader $action
     */
    public function setHeader(EventHeader $action)
    {
        if ($this->isValid($action))
        {
            $this->header = $action;
            $this->header->setGame($this->game);
        }
    }



    /**
     * @param EventAction $action
     */
    public function addAction(EventAction $action): void
    {
        $this->isValid($action);

        $this->actions[$action->getPriority()][$action->getName()] = clone $action;
        $this->actions[$action->getPriority()][$action->getName()]->setGame($this->game);

        rsort($this->actions);
    }



    /**
     * @param string $action
     */
    public function removeAction(string $action): void
    {
        foreach ($this->actions as &$actions)
            if (isset($actions[$action]))
                unset($actions[$action]);
    }



    /**
     * @param string $action
     * @return bool
     */
    public function hasAction(string $action): bool
    {
        foreach ($this->actions as $actions)
            if (isset($actions[$action]))
                return true;

        return false;
    }



    /**
     * @param EventAction $action
     * @return bool
     */
    public function isValid(EventAction $action): bool
    {
        if (!is_a($action->getEvent(), $this->event, true))
            throw new InvalidArgumentException($action->getEvent() . " is not part of " . $this->event);

        if ($action->getEvent() !== $this->event)
            throw new InvalidArgumentException($action->getName() . " must listen for " . $this->event . " not for " . $action->getEvent());

        if (!isset(static::$pRIORITIES[$action->getPriority()]))
            throw new InvalidArgumentException($action->getName() . " has an invalid priority level.");

        return true;
    }



    /**
     * @param Listener $listener
     * @param Event    $event
     */
    public function execute(Listener $listener, Event $event): void
    {
        $result = null;

        if ($this->header)
        {
            $result = $this->header->process($event, null);

            if ($this->header->returnSession() && !($result instanceof InGame))
                return;
        }

        foreach ($this->actions as $actions)
            foreach ($actions as $action)
                $action->process($event, $result);
    }

    public function unregister(): void
    {
        //...
    }



}