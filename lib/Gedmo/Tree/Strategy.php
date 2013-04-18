<?php

namespace Gedmo\Tree;

use Gedmo\Mapping\Event\AdapterInterface;

interface Strategy
{
    /**
     * NestedSet strategy
     */
    const NESTED = 'nested';

    /**
     * Closure strategy
     */
    const CLOSURE = 'closure';

    /**
     * Materialized Path strategy
     */
    const MATERIALIZED_PATH = 'materializedPath';

    /**
     * Get the name of strategy
     *
     * @return string
     */
    function getName();

    /**
     * Initialize strategy with tree listener
     *
     * @param TreeListener $listener
     */
    function __construct(TreeListener $listener);

    /**
     * Operations after metadata is loaded
     *
     * @param object $om
     * @param object $meta
     */
    function processMetadataLoad($om, $meta);

    /**
     * Operations on tree node insertion
     *
     * @param object $om - object manager
     * @param object $object - node
     * @param AdapterInterface $ea - event adapter
     * @return void
     */
    function processScheduledInsertion($om, $object, AdapterInterface $ea);

    /**
     * Operations on tree node updates
     *
     * @param object $om - object manager
     * @param object $object - node
     * @param AdapterInterface $ea - event adapter
     * @return void
     */
    function processScheduledUpdate($om, $object, AdapterInterface $ea);

    /**
     * Operations on tree node delete
     *
     * @param object $om - object manager
     * @param object $object - node
     * @return void
     */
    function processScheduledDelete($om, $object);

    /**
     * Operations on tree node removal
     *
     * @param object $om - object manager
     * @param object $object - node
     * @return void
     */
    function processPreRemove($om, $object);

    /**
     * Operations on tree node persist
     *
     * @param object $om - object manager
     * @param object $object - node
     * @return void
     */
    function processPrePersist($om, $object);

    /**
     * Operations on tree node update
     *
     * @param object $om - object manager
     * @param object $object - node
     * @return void
     */
    function processPreUpdate($om, $object);

    /**
     * Operations on tree node insertions
     *
     * @param object $om - object manager
     * @param object $object - node
     * @param AdapterInterface $ea - event adapter
     * @return void
     */
    function processPostPersist($om, $object, AdapterInterface $ea);

    /**
     * Operations on tree node updates
     *
     * @param object $om - object manager
     * @param object $object - node
     * @param AdapterInterface $ea - event adapter
     * @return void
     */
    function processPostUpdate($om, $object, AdapterInterface $ea);

    /**
     * Operations on tree node removals
     *
     * @param object $om - object manager
     * @param object $object - node
     * @param AdapterInterface $ea - event adapter
     * @return void
     */
    function processPostRemove($om, $object, AdapterInterface $ea);

    /**
     * Operations on the end of flush process
     *
     * @param object $om - object manager
     * @param AdapterInterface $ea - event adapter
     * @return void
     */
    function onFlushEnd($om, AdapterInterface $ea);
}
