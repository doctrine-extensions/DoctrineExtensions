<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Revisionable\Mapping\Event;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Revisionable\Revisionable;
use Gedmo\Revisionable\RevisionInterface;

/**
 * Doctrine event adapter for the Revisionable extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface RevisionableAdapter extends AdapterInterface
{
    /**
     * Get the default object class name used to store revisions.
     *
     * @phpstan-return class-string<RevisionInterface<Revisionable|object>>
     */
    public function getDefaultRevisionClass(): string;

    /**
     * Checks whether an identifier should be generated post insert.
     */
    public function isPostInsertGenerator(ClassMetadata $meta): bool;

    /**
     * Get the new version number for an object.
     *
     * @phpstan-return positive-int
     */
    public function getNewVersion(ClassMetadata $meta, object $object): int;
}
