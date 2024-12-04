<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable\Mapping\Event;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter for the SoftDeleteable extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface SoftDeleteableAdapter extends AdapterInterface
{
    /**
     * Get the date value.
     *
     * @param ClassMetadata<object> $meta
     * @param string                $field
     *
     * @return int|\DateTimeInterface
     */
    public function getDateValue($meta, $field);
}
