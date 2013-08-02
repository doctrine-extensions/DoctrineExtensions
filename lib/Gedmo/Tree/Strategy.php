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
    public function getName();

    /**
     * Initialize strategy with tree listener
     *
     * @param TreeListener $listener
     */
    public function __construct(TreeListener $listener);

    /**
     * Operations after metadata is loaded
     *
     * @param ObjectManager $om
     * @param ClassMetadata $meta
     */
    public function processMetadataLoad(ObjectManager $om, ClassMetadata $meta);

    /**
     * Operations on tree node insertion
     *
     * @param ObjectManager $om
     * @param object        $object - node
     */
    public function processScheduledInsertion(ObjectManager $om, $object);

    /**
     * Operations on tree node updates
     *
     * @param ObjectManager $om
     * @param object        $object - node
     */
    public function processScheduledUpdate(ObjectManager $om, $object);

    /**
     * Operations on tree node delete
     *
     * @param ObjectManager $om
     * @param object        $object - node
     */
    public function processScheduledDelete(ObjectManager $om, $object);

    /**
     * Operations on tree node removal
     *
     * @param ObjectManager $om
     * @param object        $object - node
     */
    public function processPreRemove(ObjectManager $om, $object);

    /**
     * Operations on tree node persist
     *
     * @param ObjectManager $om
     * @param object        $object - node
     */
    public function processPrePersist(ObjectManager $om, $object);

    /**
     * Operations on tree node update
     *
     * @param ObjectManager $om
     * @param object        $object - node
     */
    public function processPreUpdate(ObjectManager $om, $object);

    /**
     * Operations on tree node insertions
     *
     * @param ObjectManager $om
     * @param object        $object - node
     */
    public function processPostPersist(ObjectManager $om, $object);

    /**
     * Operations on tree node updates
     *
     * @param ObjectManager $om
     * @param object        $object - node
     */
    public function processPostUpdate(ObjectManager $om, $object);

    /**
     * Operations on tree node removals
     *
     * @param ObjectManager $om
     * @param object        $object - node
     */
    public function processPostRemove(ObjectManager $om, $object);

    /**
     * Operations on the end of flush process
     *
     * @param ObjectManager $om
     */
    public function onFlushEnd(ObjectManager $om);
}
