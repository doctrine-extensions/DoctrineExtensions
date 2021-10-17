<?php

namespace Gedmo\Sluggable\Handler;

use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;

/**
 * This adds the ability for a slug handler to change the slug just before its
 * uniqueness is ensured. It is also called if the unique options are _not_
 * set.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface SlugHandlerWithUniqueCallbackInterface extends SlugHandlerInterface
{
    /**
     * Hook for slug handlers called before it is made unique.
     *
     * @param object $object
     * @param string $slug
     *
     * @return void
     */
    public function beforeMakingUnique(SluggableAdapter $ea, array &$config, $object, &$slug);
}
