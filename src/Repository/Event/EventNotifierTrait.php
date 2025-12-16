<?php

namespace Bdf\Prime\Repository\Event;

use BadMethodCallException;
use Bdf\Prime\Events;

use function get_class;
use function sprintf;

trait EventNotifierTrait
{
    /**
     * List of listeners per event class name
     *
     * The search listener item is an array with the following structure:
     * - 0: The listener callable
     * - 1: The listener is a 'once' listener (if true, it will be removed after the first call)
     * - 2: The listener is a legacy listener (take array of arguments instead of the event object)
     *
     * The key is always the event class name. So if a legacy event name is used, it should be converted to the class name.
     *
     * @var array<string, array<list{callable, bool, bool}>>
     */
    private array $listeners = [];

    /**
     * Flag to enable / disable the notifier
     *
     * @var bool
     */
    private bool $enableEventNotifier = true;

    /**
     * Enable the event dispatcher
     *
     * @return $this
     */
    public function enableEventNotifier(): self
    {
        $this->enableEventNotifier = true;

        return $this;
    }

    /**
     * Enable the event dispatcher
     *
     * @return $this
     */
    public function disableEventNotifier(): self
    {
        $this->enableEventNotifier = false;

        return $this;
    }

    /**
     * Register listener on event
     *
     * @param string   $eventName
     * @param callable $listener
     *
     * @return $this
     */
    public function listen(string $eventName, callable $listener): self
    {
        return $this->register($eventName, $listener, false);
    }

    /**
     * Register listener.
     *
     * Will be remove on first event call
     *
     * @param string   $eventName
     * @param callable $listener
     *
     * @return $this
     */
    public function once(string $eventName, callable $listener): self
    {
        return $this->register($eventName, $listener, true);
    }

    /**
     * Check if there are listeners on this event
     *
     * @param string $eventName
     *
     * @return bool
     */
    public function hasListeners(string $eventName): bool
    {
        return isset($this->listeners[$eventName]) || isset($this->listeners[Events::eventNameToClass($eventName)]);
    }

    /**
     * Remove all listeners
     *
     * Remove listeners of event name. If the event name is null,
     * every listeners of every events will be detached
     *
     * @param string|null $eventName
     *
     * @return $this
     * @internal
     */
    public function detachAll(?string $eventName = null): self
    {
        if ($eventName !== null) {
            unset($this->listeners[$eventName]);
        } else {
            $this->listeners = [];
        }

        return $this;
    }

    /**
     * Notify event
     *
     * notify event name on listener. The event can be stopped
     * if a listener return 'false'. The notify will return the event status
     *
     * true: ok
     * false: interrupted
     *
     * @param string|RepositoryEventInterface $event
     * @param mixed  $args
     *
     * @return bool
     */
    public function notify($event, array $args = []): bool
    {
        if ($this->enableEventNotifier === false) {
            return true;
        }

        if (is_string($event)) {
            $eventClass = Events::eventNameToClass($event);
            @trigger_error(sprintf('Using legacy event name "%s" is deprecated since Prime 2.2, and will be removed in Prime 3.0. Use the event class name "%s" instead.', $event, $eventClass), E_USER_DEPRECATED);

            $event = new $eventClass(...$args);
        } else {
            $eventClass = get_class($event);

            if ($args !== []) {
                throw new BadMethodCallException('Cannot use $args argument when $event is an object');
            }
        }

        foreach ($this->listeners[$eventClass] ?? [] as $index => [$listener, $once, $isLegacy]) {
            if ($once) {
                unset($this->listeners[$eventClass][$index]);
            }

            $ret = $isLegacy ? $listener(...$event->legacyArgs()) : $listener($event);

            if ($ret === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Register listener.
     *
     * Will be remove on first event call
     *
     * @param string   $eventName
     * @param callable $listener
     * @param bool     $once
     *
     * @return $this
     */
    private function register(string $eventName, callable $listener, bool $once): self
    {
        if (!class_exists($eventName)) {
            $eventClass = Events::eventNameToClass($eventName);
            @trigger_error(sprintf('Using legacy event name "%s" is deprecated since Prime 2.2, and will be removed in Prime 3.0. Use the event class name "%s" instead.', $eventName, $eventClass), E_USER_DEPRECATED);
            $eventName = $eventClass;
            $isLegacy = true;
        } else {
            $isLegacy = false;
        }

        $this->listeners[$eventName][] = [$listener, $once, $isLegacy];

        return $this;
    }
}
