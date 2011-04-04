<?php

namespace Gedmo\Sluggable\Mapping\Event;

use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter interface
 * for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo\Sluggable\Mapping\Event
 * @subpackage SluggableAdapter
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface SluggableAdapter extends AdapterInterface
{
    /**
     * Loads the similar slugs
     *
     * @param object $object
     * @param ClassMetadata $meta
     * @param array $config
     * @param string $slug
     * @return array
     */
    function getSimilarSlugs($object, $meta, array $config, $slug);
}