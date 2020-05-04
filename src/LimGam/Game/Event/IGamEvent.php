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
 * @package LimGam\Game
 */
class IGamEvent implements EventExecutor, IGamEventListener
{



    /** @var string */
    protected $Game;

    /** @var string */
    protected $Event;

    /** @var EventHeader */
    protected $Header;

    /** @var EventAction[] */
    protected $Actions;


    public const PRIORITY_LOW    = 2;
    public const PRIORITY_NORMAL = 1;
    public const PRIORITY_HIGH   = 0;

    protected static $PRIORITIES = [
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
        $this->Game    = $game;
        $this->Event   = $event;
        $this->Actions = [];

        LimGam::GetInstance()->getServer()->getPluginManager()->registerEvent($event, $this, $priority, $this, LimGam::GetInstance());
    }



    /**
     * @return string
     */
    public function GetGame(): string
    {
        return $this->Game;
    }



    /**
     * @return string
     */
    public function GetEvent(): string
    {
        return $this->Event;
    }



    /**
     * @param EventHeader $action
     */
    public function SetHeader(EventHeader $action)
    {
        if ($this->IsValid($action))
        {
            $this->Header = $action;
            $this->Header->SetGame($this->Game);
        }
    }



    /**
     * @param EventAction $action
     */
    public function AddAction(EventAction $action): void
    {
        $this->IsValid($action);

        $this->Actions[$action->GetPriority()][$action->GetName()] = clone $action;
        $this->Actions[$action->GetPriority()][$action->GetName()]->SetGame($this->Game);

        rsort($this->Actions);
    }



    /**
     * @param string $action
     */
    public function RemoveAction(string $action): void
    {
        foreach ($this->Actions as &$actions)
            if (isset($actions[$action]))
                unset($actions[$action]);
    }



    /**
     * @param string $action
     * @return bool
     */
    public function HasAction(string $action): bool
    {
        foreach ($this->Actions as $actions)
            if (isset($actions[$action]))
                return true;

        return false;
    }



    /**
     * @param EventAction $action
     * @return bool
     */
    public function IsValid(EventAction $action): bool
    {
        if (!is_a($action->GetEvent(), $this->Event, true))
            throw new InvalidArgumentException($action->GetEvent() . " is not part of " . $this->Event);

        if ($action->GetEvent() !== $this->Event)
            throw new InvalidArgumentException($action->GetName() . " must listen for " . $this->Event . " not for " . $action->GetEvent());

        if (!isset(static::$PRIORITIES[$action->GetPriority()]))
            throw new InvalidArgumentException($action->GetName() . " has an invalid priority level.");

        return true;
    }



    /**
     * @param Listener $listener
     * @param Event    $event
     */
    public function execute(Listener $listener, Event $event): void
    {
        $result = null;

        if ($this->Header)
        {
            $result = $this->Header->Process($event, null);

            if ($this->Header->ReturnSession() && !($result instanceof InGame))
                return;
        }

        foreach ($this->Actions as $actions)
            foreach ($actions as $action)
                $action->Process($event, $result);
    }



}