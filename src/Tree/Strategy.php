<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\Event\AdapterInterface;

interface Strategy
{
    /**
     * NestedSet strategy
     */
    public const NESTED = 'nested';

    /**
     * Closure strategy
     */
    public const CLOSURE = 'closure';

    /**
     * Materialized Path strategy
     */
    public const MATERIALIZED_PATH = 'materializedPath';

    /**
     * Create a new strategy instance
     */
    public function __construct(TreeListener $listener);

    /**
     * Get the name of the strategy
     *
     * @return string
     */
    public function getName();

    /**
     * Operations after metadata is loaded
     *
     * @param ObjectManager         $om
     * @param ClassMetadata<object> $meta
     *
     * @return void
     */
    public function processMetadataLoad($om, $meta);

    /**
     * Operations on tree node insertion
     *
     * @param ObjectManager $om
     * @param object|Node   $object
     *
     * @return void
     */
    public function processScheduledInsertion($om, $object, AdapterInterface $ea);

    /**
     * Operations on tree node updates
     *
     * @param ObjectManager $om
     * @param object|Node   $object
     *
     * @return void
     */
    public function processScheduledUpdate($om, $object, AdapterInterface $ea);

    /**
     * Operations on tree node delete
     *
     * @param ObjectManager $om
     * @param object|Node   $object
     *
     * @return void
     */
    public function processScheduledDelete($om, $object);

    /**
     * Operations on tree node removal
     *
     * @param ObjectManager $om
     * @param object|Node   $object
     *
     * @return void
     */
    public function processPreRemove($om, $object);

    /**
     * Operations on tree node persist
     *
     * @param ObjectManager $om
     * @param object|Node   $object
     *
     * @return void
     */
    public function processPrePersist($om, $object);

    /**
     * Operations on tree node update
     *
     * @param ObjectManager $om
     * @param object|Node   $object
     *
     * @return void
     */
    public function processPreUpdate($om, $object);

    /**
     * Operations on tree node insertions
     *
     * @param ObjectManager $om
     * @param object|Node   $object
     *
     * @return void
     */
    public function processPostPersist($om, $object, AdapterInterface $ea);

    /**
     * Operations on tree node updates
     *
     * @param ObjectManager $om
     * @param object|Node   $object
     *
     * @return void
     */
    public function processPostUpdate($om, $object, AdapterInterface $ea);

    /**
     * Operations on tree node removals
     *
     * @param ObjectManager $om
     * @param object|Node   $object
     *
     * @return void
     */
    public function processPostRemove($om, $object, AdapterInterface $ea);

    /**
     * Operations on the end of flush process
     *
     * @param ObjectManager $om
     *
     * @return void
     */
    public function onFlushEnd($om, AdapterInterface $ea);
}
