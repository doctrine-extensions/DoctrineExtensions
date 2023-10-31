<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable\Mapping\Event;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter for the SoftDeleteable extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @method LifecycleEventArgs createPreSoftDeleteEventArgs(object $object, ObjectManager $manager)
 * @method LifecycleEventArgs createPostSoftDeleteEventArgs(object $object, ObjectManager $manager)
 */
interface SoftDeleteableAdapter extends AdapterInterface
{
    /**
     * Get the date value.
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return int|\DateTimeInterface
     */
    public function getDateValue($meta, $field);
}
