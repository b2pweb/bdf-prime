<?php

namespace Bdf\Prime\Repository;

/**
 * Subscription methods for repository events
 *
 * @template E as object
 */
interface RepositoryEventsSubscriberInterface
{
    /**
     * Register post load event
     * This event is triggered when a entity is returned by a query
     *
     * Listener arguments :
     * - The loaded entity
     * - The repository
     *
     * @param callable(E,RepositoryInterface<E>):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return $this
     */
    public function loaded(callable $listener, bool $once = false);

    /**
     * Register pre save event
     * This event is triggered before save an entity
     *
     * Listener arguments :
     * - The entity to save
     * - The repository
     * - A boolean indicating if the entity is new (i.e. should be inserted) or not
     *
     * The listener may return false to cancel saving
     *
     * @param callable(E,RepositoryInterface<E>,bool):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return $this
     */
    public function saving(callable $listener, bool $once = false);

    /**
     * Register post save event
     * This event is triggered after the entity has been successfully saved
     *
     * Listener arguments :
     * - The saved entity
     * - The repository
     * - The affected entity count (should be 1)
     * - A boolean indicating if the entity is new or not
     *
     * @param callable(E,RepositoryInterface<E>,int,bool):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return $this
     */
    public function saved(callable $listener, bool $once = false);

    /**
     * Register pre insert event
     * This event is triggered before perform the insertion of the entity
     *
     * Listener arguments :
     * - The entity to insert
     * - The repository
     *
     * The listener may return false to cancel insertion
     *
     * @param callable(E,RepositoryInterface<E>):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return $this
     */
    public function inserting(callable $listener, bool $once = false);

    /**
     * Register post insert event
     * This event is triggered after the entity has been inserted
     *
     * Listener arguments :
     * - The inserted entity
     * - The repository
     * - The affected entity count (should be 1)
     *
     * @param callable(E,RepositoryInterface<E>,int):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return $this
     */
    public function inserted(callable $listener, bool $once = false);

    /**
     * Register pre update event
     * This event is triggered before perform the entity update
     *
     * Listener arguments :
     * - The entity to update
     * - The repository
     * - Attributes to update (array of attributes names) use as in-out parameter
     *
     * The listener may return false to cancel update
     *
     * @param callable(E,RepositoryInterface<E>,\ArrayObject<int,string>):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return $this
     */
    public function updating(callable $listener, bool $once = false);

    /**
     * Register post update event
     * This event is triggered after the entity has been updated
     *
     * Listener arguments :
     * - The updated entity
     * - The repository
     * - The affected entity count (should be 1)
     *
     * @param callable(E,RepositoryInterface<E>,int):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return $this
     */
    public function updated(callable $listener, bool $once = false);

    /**
     * Register post delete event
     * This event is triggered before perform the entity deletion
     *
     * Listener arguments :
     * - The entity to delete
     * - The repository
     *
     * The listener may return false to cancel deletion
     *
     * @param callable(E,RepositoryInterface<E>):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return $this
     */
    public function deleting(callable $listener, bool $once = false);

    /**
     * Register post delete event
     * This event is triggered after the entity has been deleted
     *
     * Listener arguments :
     * - The deleted entity
     * - The repository
     * - The affected entity count (should be 1)
     *
     * @param callable(E,RepositoryInterface<E>,int):(bool|null) $listener
     * @param bool $once Register on event once
     *
     * @return $this
     */
    public function deleted(callable $listener, bool $once = false);
}
