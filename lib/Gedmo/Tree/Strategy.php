<?php

namespace Gedmo\Tree;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

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
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $meta
     */
    function processMetadataLoad(ObjectManager $om, ClassMetadata $meta);

    /**
     * Operations on tree node insertion
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param object $object - node
     */
    function processScheduledInsertion(ObjectManager $om, $object);

    /**
     * Operations on tree node updates
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param object $object - node
     */
    function processScheduledUpdate(ObjectManager $om, $object);

    /**
     * Operations on tree node delete
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param object $object - node
     */
    function processScheduledDelete(ObjectManager $om, $object);

    /**
     * Operations on tree node removal
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param object $object - node
     */
    function processPreRemove(ObjectManager $om, $object);

    /**
     * Operations on tree node persist
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param object $object - node
     */
    function processPrePersist(ObjectManager $om, $object);

    /**
     * Operations on tree node update
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param object $object - node
     */
    function processPreUpdate(ObjectManager $om, $object);

    /**
     * Operations on tree node insertions
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param object $object - node
     */
    function processPostPersist(ObjectManager $om, $object);

    /**
     * Operations on tree node updates
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param object $object - node
     */
    function processPostUpdate(ObjectManager $om, $object);

    /**
     * Operations on tree node removals
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param object $object - node
     */
    function processPostRemove(ObjectManager $om, $object);

    /**
     * Operations on the end of flush process
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     */
    function onFlushEnd(ObjectManager $om);
}
