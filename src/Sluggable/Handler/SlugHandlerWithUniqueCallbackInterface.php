<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Sluggable\Handler;

use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;

/**
 * This adds the ability for a slug handler to change the slug just before its
 * uniqueness is ensured. It is also called if the unique options are _not_
 * set.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
interface SlugHandlerWithUniqueCallbackInterface extends SlugHandlerInterface
{
    /**
     * Hook for slug handlers called before it is made unique.
     *
     * @param array<string, mixed> $config
     * @param object               $object
     * @param string               $slug
     *
     * @return void
     */
    public function beforeMakingUnique(SluggableAdapter $ea, array &$config, $object, &$slug);
}
